@php
    $weeks = [];
    $monthStart = \Illuminate\Support\Carbon::create($turno->year, $turno->month, 1);
    $offset = $monthStart->dayOfWeekIso - 1;
    $date = $monthStart->copy()->subDays($offset);
    for ($week = 0; $week < 6; $week++) {
        $days = [];
        for ($weekday = 0; $weekday < 7; $weekday++) {
            $days[] = $date->copy();
            $date->addDay();
        }
        $weeks[] = $days;
    }
    $monthName = ucfirst($monthStart->translatedFormat('F Y'));
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="1400" height="1080" viewBox="0 0 1400 1080">
    <rect width="1400" height="1080" fill="#ffffff"/>
    <image x="64" y="46" width="210" height="64" preserveAspectRatio="xMinYMid meet" href="data:image/png;base64,{{ $logoData }}"/>
    <text x="64" y="152" fill="#35234f" font-family="Nunito, sans-serif" font-size="38" font-weight="900">{{ $monthName }}</text>
    <text x="66" y="180" fill="#716c77" font-family="Nunito, sans-serif" font-size="16" font-weight="700">TURNOS Y ACTIVIDADES · BIBLIOTECA INSECTARIA</text>
    <image x="1250" y="42" width="92" height="92" preserveAspectRatio="xMidYMid meet" href="data:image/png;base64,{{ $mascotaData }}"/>
    <line x1="64" y1="202" x2="1336" y2="202" stroke="#e9d9ff" stroke-width="2"/>
    @foreach (['LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB', 'DOM'] as $index => $weekday)
        @php
            $x = 64 + $index * 182;
        @endphp
        <text x="{{ $x + 8 }}" y="228" fill="#716c77" font-family="Nunito, sans-serif" font-size="14" font-weight="900" letter-spacing="1">{{ $weekday }}</text>
    @endforeach
    @foreach ($weeks as $weekIndex => $week)
        @foreach ($week as $dayIndex => $calendarDate)
            @php
                $x = 64 + $dayIndex * 182;
                $y = 242 + $weekIndex * 124;
                $dateKey = $calendarDate->toDateString();
                $isInMonth = $calendarDate->month === $turno->month;
                $dayEntry = $isInMonth ? $dias->get($dateKey) : null;
                $activities = $isInMonth ? $actividadesPorFecha->get($dateKey, collect()) : collect();
                $isOpen = $dayEntry?->is_open ?? false;
            @endphp
            <rect x="{{ $x }}" y="{{ $y }}" width="174" height="114" rx="9" fill="{{ ! $isInMonth ? '#ffffff' : ($isOpen ? '#ffffff' : '#f7f5f8') }}" stroke="{{ ! $isInMonth ? '#ffffff' : ($isOpen ? '#e5e0e9' : '#ece8ef') }}" stroke-width="1.5"/>
            @if ($isInMonth)
                <text x="{{ $x + 12 }}" y="{{ $y + 23 }}" fill="#35234f" font-family="Nunito, sans-serif" font-size="18" font-weight="900">{{ $calendarDate->day }}</text>
                @if ($dayEntry && ! $dayEntry->is_open)
                    <text x="{{ $x + 12 }}" y="{{ $y + 48 }}" fill="#8b8491" font-family="Nunito, sans-serif" font-size="12" font-weight="700">CERRADO</text>
                @elseif ($dayEntry)
                    @foreach ($dayEntry->volunteers->take(3) as $volunteerIndex => $volunteer)
                        <text x="{{ $x + 12 }}" y="{{ $y + 46 + $volunteerIndex * 17 }}" fill="#563884" font-family="Nunito, sans-serif" font-size="12" font-weight="800">{{ \Illuminate\Support\Str::limit($volunteer->name, 22) }}</text>
                    @endforeach
                    @if ($dayEntry->assignment_notes)
                        @php
                            $noteY = $y + 48 + min(3, $dayEntry->volunteers->count()) * 17;
                        @endphp
                        <text x="{{ $x + 12 }}" y="{{ $noteY }}" fill="#a34c82" font-family="Nunito, sans-serif" font-size="11" font-weight="700">{{ \Illuminate\Support\Str::limit($dayEntry->assignment_notes, 24) }}</text>
                    @endif
                @endif
                @foreach ($activities->take(1) as $activity)
                    <text x="{{ $x + 12 }}" y="{{ $y + 100 }}" fill="#a34c82" font-family="Nunito, sans-serif" font-size="11" font-weight="800">{{ \Illuminate\Support\Str::limit($activity->title, 24) }}</text>
                @endforeach
            @endif
        @endforeach
    @endforeach
</svg>
