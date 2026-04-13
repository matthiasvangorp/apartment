<div>
    <h2 class="page-title">Appliances</h2>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 18px;">
        @foreach ($appliances as $appliance)
            @php
                $today = \Carbon\Carbon::today();
            @endphp
            <div class="panel" style="margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                    <div>
                        <h3 class="panel-title" style="margin-bottom: 4px;">{{ $appliance->name }}</h3>
                        <div class="muted" style="font-size: 0.82rem;">
                            {{ $appliance->brand ?? '—' }}@if ($appliance->model) · {{ $appliance->model }}@endif
                            @if ($appliance->location) · {{ $appliance->location }} @endif
                        </div>
                    </div>
                    @if ($appliance->manual)
                        <a href="/documents/{{ $appliance->manual->id }}" class="badge cat-appliance_manual" style="text-decoration: none;">📄 manual</a>
                    @endif
                </div>

                @if ($appliance->maintenanceTasks->isEmpty())
                    <div class="muted" style="margin-top: 14px; font-size: 0.85rem;">No maintenance schedule.</div>
                @else
                    <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 10px;">
                        @foreach ($appliance->maintenanceTasks as $task)
                            @php
                                $isOverdue = $task->next_due_on && $task->next_due_on->lt($today);
                                $isSoon = $task->next_due_on && $task->next_due_on->between($today, $today->copy()->addDays(30));
                            @endphp
                            <div style="border: 1px solid var(--card-border); border-radius: 6px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.92rem; font-weight: 500;">{{ $task->title }}</div>
                                    <div class="muted" style="font-size: 0.76rem; margin-top: 2px;">
                                        every {{ $task->cadence_months }} mo ·
                                        @if ($task->last_done_on)
                                            last done {{ $task->last_done_on->toDateString() }}
                                        @else
                                            never done
                                        @endif
                                    </div>
                                    <div style="font-size: 0.78rem; margin-top: 2px;">
                                        next:
                                        <strong @class([
                                            'muted',
                                            'overdue' => $isOverdue,
                                        ]) @style([
                                            'color: var(--accent-red)' => $isOverdue,
                                            'color: var(--accent-orange)' => $isSoon,
                                        ])>
                                            {{ $task->next_due_on?->toDateString() ?? '—' }}
                                            @if ($isOverdue) (overdue) @endif
                                        </strong>
                                    </div>
                                </div>
                                <button type="button" class="btn success" wire:click="markDone({{ $task->id }})" wire:loading.attr="disabled">
                                    Mark done
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
