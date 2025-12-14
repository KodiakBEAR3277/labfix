@props(['ticket'])

<div class="card">
    <h2 class="card-title">Activity Timeline</h2>
    <div class="timeline">
        @forelse($ticket->transactions()->with('user')->latest('created_at')->get() as $transaction)
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <span class="timeline-title">
                            @switch($transaction->action)
                                @case('created')
                                    🎫 Ticket Created
                                    @break
                                @case('status_changed')
                                    🔄 Status Updated
                                    @break
                                @case('assigned')
                                    👤 Assigned
                                    @break
                                @case('priority_changed')
                                    ⚠️ Priority Changed
                                    @break
                                @case('updated')
                                    ✏️ Ticket Updated
                                    @break
                                @case('deleted')
                                    🗑️ Ticket Cancelled
                                    @break
                                @case('restored')
                                    ♻️ Ticket Restored
                                    @break
                                @default
                                    📌 Activity
                            @endswitch
                        </span>
                        <span class="timeline-time">{{ $transaction->created_at->format('M d, Y g:i A') }}</span>
                    </div>
                    <p class="timeline-text">
                        {{ $transaction->description }}
                    </p>
                    @if($transaction->old_value || $transaction->new_value)
                        <div style="margin-top: 0.5rem; padding: 0.5rem; background: rgba(45, 212, 191, 0.1); border-radius: 4px; font-size: 0.85rem;">
                            @if($transaction->old_value)
                                <span style="color: #ef4444;">{{ $transaction->old_value }}</span>
                                <span style="color: #9ca3af;"> → </span>
                            @endif
                            @if($transaction->new_value)
                                <span style="color: #10b981;">{{ $transaction->new_value }}</span>
                            @endif
                        </div>
                    @endif
                    <div style="margin-top: 0.25rem; font-size: 0.85rem; color: #9ca3af;">
                        by {{ $transaction->user->full_name }}
                    </div>
                </div>
            </div>
        @empty
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <span class="timeline-title">📝 No Activity Yet</span>
                        <span class="timeline-time">{{ $ticket->created_at->format('M d, Y g:i A') }}</span>
                    </div>
                    <p class="timeline-text">
                        Ticket created by {{ $ticket->reporter->full_name }}
                    </p>
                </div>
            </div>
        @endforelse
    </div>
</div>