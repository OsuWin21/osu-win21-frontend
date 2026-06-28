<div class="col-sm-4 d-flex px-0 align-items-center justify-content-end ms-auto">
    <div class="card-profile-modes position-relative">
        <div class="d-flex align-items-center flex-wrap gap-3 justify-content-end">
            <!-- Mode Selector -->
            <div class="btn-group">
                <button class="btn btn-gradient-primary dropdown-toggle" type="button" id="modeDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    @switch($mode)
                        @case(0) Standard @break
                        @case(1) Taiko @break
                        @case(2) Catch @break
                        @case(3) Mania @break
                        @default Standard
                    @endswitch
                </button>
                <ul class="dropdown-menu" aria-labelledby="modeDropdown">
                    @foreach ([0 => 'Standard', 1 => 'Taiko', 2 => 'Catch', 3 => 'Mania'] as $m => $name)
                        <li>
                            <a class="dropdown-item {{ $mode == $m ? 'active' : '' }}"
                                href="{{ request()->routeIs('profile') 
                                    ? route('profile', [
                                        'id' => $user->id,
                                        'mode' => $m,
                                        'rx' => $rx,
                                    ])
                                    : (request()->routeIs('leaderboard')
                                        ? route('leaderboard', [
                                            'mode' => $m,
                                            'rx' => $rx,
                                            'sort' => request('sort', 'pp')
                                        ])
                                        : '#')
                                }}">
                                {{ $name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <!-- RX Selector -->
            <div class="btn-group">
                <button class="btn btn-gradient-primary dropdown-toggle" type="button" id="rxDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    @switch($rx)
                        @case(0) Vanilla @break
                        @case(4) Relax @break
                        @case(8) AutoPilot @break
                        @default Vanilla
                    @endswitch
                </button>
                <ul class="dropdown-menu" aria-labelledby="rxDropdown">
                    @foreach ([0 => 'Vanilla', 4 => 'Relax', 8 => 'AutoPilot'] as $r => $name)
                        @php
                            $disabled = ($r == 4 && $mode == 3) || ($r == 8 && $mode != 0);
                        @endphp
                        <li>
                            <a class="dropdown-item {{ $disabled ? 'disabled text-muted' : '' }} {{ $rx == $r ? 'active' : '' }}"
                                href="{{ !$disabled && request()->routeIs('profile')
                                    ? route('profile', [
                                        'id' => $user->id,
                                        'mode' => $mode,
                                        'rx' => $r,
                                    ])
                                    : (!$disabled && request()->routeIs('leaderboard')
                                        ? route('leaderboard', [
                                            'mode' => $mode,
                                            'rx' => $r,
                                            'sort' => request('sort', 'pp')
                                        ])
                                        :   '#')}}"
                                @if ($disabled) aria-disabled="true" @endif>
                                {{ $name }}
                                @if ($disabled)
                                    <small class="text-muted ms-2">(Not available)</small>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
