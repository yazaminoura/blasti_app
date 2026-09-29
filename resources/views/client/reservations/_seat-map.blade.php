{{--
    Bus seen from above. Horizontal on computers (driver on the left), vertical on phones (driver on top).
    Classic 2 + 2 layout: row r holds seats 4r+1, 4r+2 | aisle | 4r+3, 4r+4 (1 and 4 are windows).
    Params: $totalSeats, $reservedSeats (taken), $form (id of the form the checkboxes belong to),
            $selected (checked seats), $max (seats allowed, 1 = radio-like single choice)
--}}
@php
    $totalSeats = (int) $totalSeats;
    $rows = (int) ceil($totalSeats / 4);
    $selected = array_map('intval', (array) ($selected ?? []));
    $max = (int) ($max ?? config('safar.max_sieges'));
@endphp
<div class="bl-seatmap" data-max="{{ $max }}">
    <div class="bl-seatmap-legend">
        <span><i class="bl-seat-dot is-free"></i>{{ __('Libre') }}</span>
        <span><i class="bl-seat-dot is-selected"></i>{{ __('Votre choix') }}</span>
        <span><i class="bl-seat-dot is-taken"></i>{{ __('Occupé') }}</span>
        <span class="text-muted"><i class="isax isax-sun-1"></i>{{ __('Fenêtre') }}</span>
    </div>

    <div class="bl-bus-scroll">
        <div class="bl-bus">
            <div class="bl-bus-front">
                <span class="bl-bus-wheel" title="{{ __('Conducteur') }}"><i class="fa fa-dharmachakra"></i></span>
                <span class="bl-bus-door">{{ __('Porte') }}</span>
            </div>
            <div class="bl-bus-rows">
                @for ($row = 0; $row < $rows; $row++)
                    <div class="bl-bus-row">
                        @foreach ([1, 2, 'aisle', 3, 4] as $offset)
                            @if ($offset === 'aisle')
                                <span class="bl-bus-aisle">{{ $row + 1 }}</span>
                                @continue
                            @endif
                            @php $num = $row * 4 + $offset; @endphp
                            @if ($num > $totalSeats)
                                <span class="bl-seat is-empty"></span>
                                @continue
                            @endif
                            @php
                                $taken = in_array($num, $reservedSeats);
                                $window = in_array($offset, [1, 4]);
                            @endphp
                            <label class="bl-seat {{ $taken ? 'is-taken' : 'is-free' }} {{ $window ? 'is-window' : '' }}"
                                   title="{{ $taken ? __('Siège :num · occupé', ['num' => $num]) : ($window ? __('Siège :num · fenêtre', ['num' => $num]) : __('Siège :num · couloir', ['num' => $num])) }}">
                                <input type="checkbox" name="seats[]" id="seat-{{ $num }}" value="{{ $num }}" form="{{ $form }}" class="seat-input"
                                       @disabled($taken) @checked(! $taken && in_array($num, $selected))>
                                <span class="bl-seat-num">{{ $num }}</span>
                            </label>
                        @endforeach
                    </div>
                @endfor
            </div>
            <div class="bl-bus-back"></div>
        </div>
    </div>
</div>
