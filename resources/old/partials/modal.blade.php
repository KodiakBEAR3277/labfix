{{-- 
    Modal Partial
    File: resources/views/partials/modal.blade.php
    
    Usage:
    @include('partials.modal', [
        'id' => 'confirmModal',
        'title' => 'Confirm Action',
        'body' => 'Are you sure?',
        'actions' => true
    ])
    
    Or with custom content:
    @component('partials.modal', ['id' => 'customModal', 'title' => 'Custom Modal'])
        <p>Your custom content here</p>
        <button class="btn btn-primary">Custom Action</button>
    @endcomponent
--}}

@props([
    'id' => 'modal',
    'title' => 'Modal Title',
    'body' => null,
    'actions' => false,
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel'
])

<div class="modal-overlay" id="{{ $id }}">
    <div class="modal">
        <h2 class="modal-header">{{ $title }}</h2>
        
        <div class="modal-body">
            @if($body)
                {!! $body !!}
            @else
                {{ $slot }}
            @endif
        </div>
        
        @if($actions)
            <div class="modal-actions">
                <button class="btn btn-cancel" onclick="closeModal('{{ $id }}')">
                    {{ $cancelText }}
                </button>
                <button class="btn btn-confirm" onclick="confirmModal('{{ $id }}')">
                    {{ $confirmText }}
                </button>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    function confirmModal(modalId) {
        // Handle confirmation logic
        closeModal(modalId);
    }
    
    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('active');
        }
    });
</script>
@endpush