const allowedTags = new Set([
    'A', 'B', 'BLOCKQUOTE', 'BR', 'DIV', 'EM', 'H2', 'H3', 'I', 'LI', 'OL', 'P', 'STRONG', 'U', 'UL',
]);
const removableTags = new Set(['IFRAME', 'OBJECT', 'SCRIPT', 'STYLE', 'SVG', 'TEMPLATE']);

function safeHref(value) {
    const href = value.replace(/[\u0000-\u0020\u007f]/g, '').trim();
    if (!href || href.length > 2048 || href.startsWith('//')) return null;

    const scheme = href.match(/^([a-z][a-z\d+.-]*):/i)?.[1]?.toLowerCase();
    return !scheme || ['http', 'https', 'mailto'].includes(scheme) ? href : null;
}

function cleanNode(node) {
    for (const child of [...node.childNodes]) {
        if (child.nodeType !== Node.ELEMENT_NODE) continue;

        if (removableTags.has(child.tagName)) {
            child.remove();
            continue;
        }

        if (!allowedTags.has(child.tagName)) {
            cleanNode(child);
            child.replaceWith(...child.childNodes);
            continue;
        }

        const href = child.tagName === 'A' ? safeHref(child.getAttribute('href') || '') : null;
        for (const attribute of [...child.attributes]) child.removeAttribute(attribute.name);

        if (child.tagName === 'A') {
            if (!href) {
                cleanNode(child);
                child.replaceWith(...child.childNodes);
                continue;
            }

            child.setAttribute('href', href);
            child.setAttribute('target', '_blank');
            child.setAttribute('rel', 'noopener noreferrer');
        }

        cleanNode(child);
    }
}

function sanitizedFragment(html) {
    const template = document.createElement('template');
    template.innerHTML = html;
    cleanNode(template.content);
    return template.content;
}

document.querySelectorAll('[data-rich-text-editor]').forEach((editor) => {
    const surface = editor.querySelector('[data-rich-text-surface]');
    const input = editor.querySelector('[data-rich-text-input]');
    const form = editor.closest('form');
    const buttons = [...editor.querySelectorAll('[data-editor-command]')];

    surface.append(sanitizedFragment(input.value));

    const sync = () => {
        const clean = document.createElement('div');
        clean.append(sanitizedFragment(surface.innerHTML));
        input.value = clean.innerHTML;
    };

    const selectionIsInside = () => {
        const selection = window.getSelection();
        return !!selection?.rangeCount && surface.contains(selection.getRangeAt(0).commonAncestorContainer);
    };

    const updateToolbar = () => {
        if (!selectionIsInside()) return;

        buttons.forEach((button) => {
            const command = button.dataset.editorCommand;
            let active = false;

            if (['bold', 'italic', 'underline', 'insertUnorderedList', 'insertOrderedList'].includes(command)) {
                active = document.queryCommandState(command);
            } else if (command === 'formatBlock') {
                const selection = window.getSelection();
                let node = selection?.anchorNode;
                if (node?.nodeType === Node.TEXT_NODE) node = node.parentElement;
                const block = node?.closest?.('p, h2, h3, blockquote');
                active = block?.tagName.toLowerCase() === button.dataset.editorValue;
            }

            if (button.hasAttribute('aria-pressed')) {
                button.setAttribute('aria-pressed', String(active));
            }
        });
    };

    const insertLink = () => {
        const selection = window.getSelection();
        const selectedText = selection?.toString() || '';
        const label = selectedText || window.prompt('Texto que se mostrará para el enlace:');
        if (!label) return;

        const url = window.prompt('Dirección del enlace (https://…):');
        const href = url && safeHref(url);
        if (!href) return;

        if (selectedText) {
            document.execCommand('createLink', false, href);
            return;
        }

        const link = document.createElement('a');
        link.href = href;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = label;
        document.execCommand('insertHTML', false, link.outerHTML);
    };

    const activeBlockquote = () => {
        const selection = window.getSelection();
        if (!selection?.rangeCount || !surface.contains(selection.getRangeAt(0).commonAncestorContainer)) {
            return null;
        }

        let node = selection.anchorNode;
        if (node?.nodeType === Node.TEXT_NODE) node = node.parentElement;

        const quote = node?.closest?.('blockquote');
        return quote && surface.contains(quote) ? quote : null;
    };

    const exitBlockquote = () => {
        const selection = window.getSelection();
        if (!selection?.rangeCount || !selection.getRangeAt(0).collapsed) return false;

        const quote = activeBlockquote();
        if (!quote) return false;

        const cursor = selection.getRangeAt(0);
        const trailingContent = document.createRange();
        trailingContent.selectNodeContents(quote);
        trailingContent.setStart(cursor.startContainer, cursor.startOffset);

        const trailingBlocks = [];
        const paragraph = document.createElement('p');
        for (const node of [...trailingContent.extractContents().childNodes]) {
            if (node.nodeType === Node.ELEMENT_NODE && ['P', 'DIV'].includes(node.tagName)) {
                while (node.firstChild) paragraph.append(node.firstChild);
            } else if (node.nodeType === Node.ELEMENT_NODE && ['BLOCKQUOTE', 'H2', 'H3', 'OL', 'UL'].includes(node.tagName)) {
                trailingBlocks.push(node);
            } else {
                paragraph.append(node);
            }
        }

        if (!paragraph.textContent.trim() && !paragraph.querySelector('br')) {
            paragraph.replaceChildren(document.createElement('br'));
        }

        if (!quote.textContent.trim()) {
            quote.replaceWith(paragraph);
        } else {
            quote.after(paragraph);
        }

        let lastInsertedBlock = paragraph;
        trailingBlocks.forEach((block) => {
            lastInsertedBlock.after(block);
            lastInsertedBlock = block;
        });

        const nextCursor = document.createRange();
        nextCursor.selectNodeContents(paragraph);
        nextCursor.collapse(true);
        selection.removeAllRanges();
        selection.addRange(nextCursor);

        return true;
    };

    const insertQuoteLineBreak = () => {
        const selection = window.getSelection();
        if (!selection?.rangeCount) return;

        const range = selection.getRangeAt(0);
        range.deleteContents();
        const lineBreak = document.createElement('br');
        range.insertNode(lineBreak);
        range.setStartAfter(lineBreak);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
    };

    buttons.forEach((button) => {
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => {
            surface.focus();
            const command = button.dataset.editorCommand;

            if (command === 'link') {
                insertLink();
            } else if (command === 'formatBlock') {
                document.execCommand(command, false, `<${button.dataset.editorValue}>`);
            } else {
                document.execCommand(command, false);
            }

            sync();
            updateToolbar();
        });
    });

    surface.addEventListener('input', sync);
    surface.addEventListener('keyup', updateToolbar);
    surface.addEventListener('mouseup', updateToolbar);
    surface.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && activeBlockquote()) {
            event.preventDefault();

            if (event.ctrlKey || event.metaKey) {
                insertQuoteLineBreak();
            } else {
                exitBlockquote();
            }

            sync();
            updateToolbar();
            return;
        }

        if (!(event.ctrlKey || event.metaKey)) return;

        const key = event.key.toLowerCase();
        const commands = { b: 'bold', i: 'italic', u: 'underline' };
        if (commands[key]) {
            event.preventDefault();
            document.execCommand(commands[key], false);
        } else if (key === 'k') {
            event.preventDefault();
            insertLink();
        }

        sync();
        updateToolbar();
    });

    surface.addEventListener('paste', (event) => {
        event.preventDefault();
        const html = event.clipboardData.getData('text/html');

        if (html) {
            const pastedContent = document.createElement('div');
            pastedContent.append(sanitizedFragment(html));
            document.execCommand('insertHTML', false, pastedContent.innerHTML);
        } else {
            document.execCommand('insertText', false, event.clipboardData.getData('text/plain'));
        }

        sync();
    });

    document.addEventListener('selectionchange', updateToolbar);
    form.addEventListener('submit', sync);
});
