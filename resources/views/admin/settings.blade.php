@extends('layouts.app')

@section('title', 'System Settings')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>System Settings</h1>
            <p>Configure system-wide settings and preferences</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="settings-grid">
                <!-- General Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">⚙️</div>
                        <h2 class="card-title">General Settings</h2>
                    </div>
                    
                    @foreach($settings['general'] as $setting)
                        <div class="form-group">
                            <label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</label>
                            @if($setting->type === 'string')
                                <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}">
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Notification Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">🔔</div>
                        <h2 class="card-title">Notification Settings</h2>
                    </div>

                    @foreach($settings['notifications'] as $setting)
                        <div class="toggle-item">
                            <div class="toggle-info">
                                <h4>{{ ucwords(str_replace('_', ' ', str_replace('_enabled', '', $setting->key))) }}</h4>
                            </div>
                            <div class="toggle-switch {{ $setting->value ? 'active' : '' }}" onclick="toggleCheckbox(this, '{{ $setting->key }}')">
                                <div class="toggle-slider"></div>
                            </div>
                            <input type="checkbox" name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" value="1" {{ $setting->value ? 'checked' : '' }} style="display: none;">
                        </div>
                    @endforeach
                </div>

                <!-- Ticket Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">📋</div>
                        <h2 class="card-title">Ticket Settings</h2>
                    </div>

                    @foreach($settings['tickets'] as $setting)
                        <div class="form-group">
                            <label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</label>
                            
                            @if($setting->key === 'ticket_number_format')
                                {{-- Special handling for ticket number format --}}
                                <select name="settings[{{ $setting->key }}]">
                                    @php
                                        $formats = [
                                            'format_1' => 'TKT-YYYY-0001 (e.g., TKT-2025-0001)',
                                            'format_2' => 'TKTYYYY0001 (e.g., TKT20250001)',
                                            'format_3' => 'TKT-YYYYMM-0001 (e.g., TKT-202512-0001)',
                                            'format_4' => 'TKT-YYYYMMDD-0001 (e.g., TKT-20251209-0001)',
                                            'format_5' => 'TICKET-YYYY-0001 (e.g., TICKET-2025-0001)',
                                        ];
                                    @endphp
                                    
                                    @foreach($formats as $key => $label)
                                        <option value="{{ $key }}" {{ $setting->value === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="help-text">This format will be used for all new tickets. Existing tickets won't change.</p>
                                
                            @elseif($setting->type === 'integer')
                                <input type="number" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" min="0">
                                
                            @elseif($setting->type === 'boolean')
                                <div class="toggle-item">
                                    <div class="toggle-switch {{ $setting->value ? 'active' : '' }}" onclick="toggleCheckbox(this, '{{ $setting->key }}')">
                                        <div class="toggle-slider"></div>
                                    </div>
                                    <input type="checkbox" name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" value="1" {{ $setting->value ? 'checked' : '' }} style="display: none;">
                                </div>
                                
                            @else
                                <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}">
                            @endif
                        </div>
                    @endforeach
                    
                    <p class="info-label">Note: to disable the automatic functions, simply place the values at 0</p>
                </div>

                <!-- Maintenance Mode -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="card-icon">🔧</div>
                        <h2 class="card-title">Maintenance Mode</h2>
                    </div>

                    @foreach($settings['maintenance'] as $setting)
                        @if($setting->type === 'boolean')
                            <div class="toggle-item">   
                                <div class="toggle-info">
                                    <h4>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</h4>
                                </div>
                                <div class="toggle-switch {{ $setting->value ? 'active' : '' }}" onclick="toggleCheckbox(this, '{{ $setting->key }}')">
                                    <div class="toggle-slider"></div>
                                </div>
                                <input type="checkbox" name="settings[{{ $setting->key }}]" id="{{ $setting->key }}" value="1" {{ $setting->value ? 'checked' : '' }} style="display: none;">
                            </div>
                        @else
                            <div class="form-group">
                                <label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</label>
                                <textarea name="settings[{{ $setting->key }}]">{{ $setting->value }}</textarea>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons" style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Save All Changes</button>
                <button type="button" class="btn btn-secondary" onclick="location.reload()">Cancel</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    function toggleCheckbox(toggleElement, checkboxId) {
        toggleElement.classList.toggle('active');
        const checkbox = document.getElementById(checkboxId);
        checkbox.checked = toggleElement.classList.contains('active');
    }
</script>
@endpush