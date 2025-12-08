@extends('layouts.app')

@section('title', 'Lab Configuration')

@section('navigation')
    <x-nav.admin />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Lab Configuration</h1>
                <p style="color: #9ca3af;">Manage lab layouts and equipment assignments</p>
            </div>
            <button class="btn-primary" onclick="openAddLabModal()">+ Add New Lab</button>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #34d399;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #ef4444;">
                {{ session('error') }}
            </div>
        @endif

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Labs</div>
                <div class="stat-value">{{ $stats['total_labs'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Equipment</div>
                <div class="stat-value">{{ $stats['total_equipment'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Operational</div>
                <div class="stat-value">{{ $stats['total_operational'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Under Maintenance</div>
                <div class="stat-value">{{ $stats['total_maintenance'] }}</div>
            </div>
        </div>

        <!-- Labs Grid -->
        <div class="labs-grid">
            @forelse($labs as $lab)
                <div class="lab-card">
                    <div class="lab-header">
                        <div class="lab-title-section">
                            <div class="lab-icon">💻</div>
                            <div>
                                <h2 class="lab-title">{{ $lab->name }}</h2>
                                <p style="color: #9ca3af; font-size: 0.85rem;">{{ $lab->code }}</p>
                            </div>
                        </div>
                        <div class="lab-actions">
                            <button class="action-btn" onclick="editLab({{ $lab->id }})">Edit</button>
                            <form action="{{ route('admin.labs.toggle-status', $lab->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="action-btn">
                                    {{ $lab->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.labs.destroy', $lab->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this lab?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>

                    <div class="lab-info-grid">
                        <div class="info-item">
                            <div class="info-label">Location</div>
                            <div class="info-value">{{ $lab->location ?? 'Not specified' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Capacity</div>
                            <div class="info-value">{{ $lab->capacity }} Workstations</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Operational</div>
                            <div class="info-value" style="color: {{ $lab->operational_count === $lab->equipment->count() ? '#34d399' : '#fbbf24' }};">
                                {{ $lab->operational_count }} / {{ $lab->equipment->count() }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="status-badge {{ $lab->is_active ? 'status-active' : 'status-inactive' }}">
                                    {{ $lab->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($lab->description)
                        <p style="color: #9ca3af; font-size: 0.9rem; margin: 1rem 0;">{{ $lab->description }}</p>
                    @endif

                    <div class="equipment-section">
                        <div class="equipment-header">
                            <h3 class="section-title">Equipment Layout ({{ $lab->equipment->count() }} items)</h3>
                            <button class="btn-add" onclick="addEquipment({{ $lab->id }})">+ Add Equipment</button>
                        </div>
                        <div class="equipment-grid">
                            @forelse($lab->equipment->take(20) as $equipment)
                                @php
                                    $statusClass = match($equipment->status) {
                                        'operational' => 'equipment-working',
                                        'has-issue' => 'equipment-issue',
                                        'maintenance' => 'equipment-maintenance',
                                        default => ''
                                    };
                                    $icon = match($equipment->type) {
                                        'computer' => '💻',
                                        'printer' => '🖨️',
                                        'projector' => '📽️',
                                        default => '🖥️'
                                    };
                                @endphp
                                <div class="equipment-item {{ $statusClass }}" onclick="editEquipment({{ $equipment->id }})">
                                    <div class="tooltip">
                                        {{ $equipment->equipment_code }}: {{ ucfirst($equipment->status) }}
                                        @if($equipment->notes)
                                            <br>{{ Str::limit($equipment->notes, 50) }}
                                        @endif
                                    </div>
                                    <div class="equipment-icon">{{ $icon }}</div>
                                    <div class="equipment-id">{{ $equipment->equipment_code }}</div>
                                </div>
                            @empty
                                <div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #9ca3af;">
                                    <p>No equipment added yet</p>
                                    <button class="btn-add" style="margin-top: 1rem;" onclick="addEquipment({{ $lab->id }})">+ Add Equipment</button>
                                </div>
                            @endforelse
                        </div>
                        @if($lab->equipment->count() > 20)
                            <p style="text-align: center; margin-top: 1rem; color: #9ca3af; font-size: 0.9rem;">
                                Showing 20 of {{ $lab->equipment->count() }} items
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state" style="grid-column: 1/-1;">
                    <div class="empty-icon">🏫</div>
                    <h3>No Labs Configured</h3>
                    <p>Create your first lab to get started</p>
                    <button class="btn-primary" style="margin-top: 1rem;" onclick="openAddLabModal()">+ Add New Lab</button>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Add/Edit Lab Modal -->
    <div class="modal-overlay" id="labModal">
        <div class="modal">
            <h2 class="modal-header" id="labModalTitle">Add New Lab</h2>
            <form id="labForm" method="POST" action="">
                @csrf
                <input type="hidden" name="_method" id="labMethod" value="POST">
                
                <div class="form-group">
                    <label>Lab Name *</label>
                    <input type="text" name="name" id="labName" placeholder="e.g., Computer Lab A" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Lab Code *</label>
                        <input type="text" name="code" id="labCode" placeholder="e.g., LAB-A" required>
                    </div>
                    <div class="form-group">
                        <label>Capacity *</label>
                        <input type="number" name="capacity" id="labCapacity" placeholder="Number of workstations" required min="1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" id="labLocation" placeholder="e.g., Building 1, 2nd Floor">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="labDescription" placeholder="Optional description of the lab"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-save">Save Lab</button>
                    <button type="button" class="btn btn-cancel" onclick="closeLabModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Equipment Modal -->
    <div class="modal-overlay" id="equipmentModal">
        <div class="modal">
            <h2 class="modal-header" id="equipmentModalTitle">Add Equipment</h2>
            <form id="equipmentForm" method="POST" action="">
                @csrf
                <input type="hidden" name="_method" id="equipmentMethod" value="POST">
                <input type="hidden" name="lab_id" id="equipmentLabId">
                
                <div class="form-group">
                    <label>Equipment Code *</label>
                    <input type="text" name="equipment_code" id="equipmentCode" placeholder="e.g., PC-01" required>
                </div>
                
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" id="equipmentType" required>
                        <option value="computer">Computer</option>
                        <option value="printer">Printer</option>
                        <option value="projector">Projector</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="equipmentStatus" required>
                        <option value="operational">Operational</option>
                        <option value="has-issue">Has Issue</option>
                        <option value="maintenance">Under Maintenance</option>
                        <option value="retired">Retired</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="equipmentNotes" placeholder="Optional notes about this equipment"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-save">Save Equipment</button>
                    <button type="button" class="btn btn-cancel" onclick="closeEquipmentModal()">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteEquipmentBtn" style="display: none;" onclick="deleteEquipment()">Delete</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let currentEquipmentId = null;

    // Lab Modal Functions
    function openAddLabModal() {
        document.getElementById('labModalTitle').textContent = 'Add New Lab';
        document.getElementById('labForm').action = '{{ route("admin.labs.store") }}';
        document.getElementById('labMethod').value = 'POST';
        document.getElementById('labForm').reset();
        document.getElementById('labModal').classList.add('active');
    }

    function closeLabModal() {
        document.getElementById('labModal').classList.remove('active');
    }

    function editLab(labId) {
        fetch(`/admin/labs/${labId}/edit`)
            .then(response => response.json())
            .then(lab => {
                document.getElementById('labModalTitle').textContent = 'Edit Lab';
                document.getElementById('labForm').action = `/admin/labs/${labId}`;
                document.getElementById('labMethod').value = 'PUT';
                document.getElementById('labName').value = lab.name;
                document.getElementById('labCode').value = lab.code;
                document.getElementById('labCapacity').value = lab.capacity;
                document.getElementById('labLocation').value = lab.location || '';
                document.getElementById('labDescription').value = lab.description || '';
                document.getElementById('labModal').classList.add('active');
            });
    }

    // Equipment Modal Functions
    function addEquipment(labId) {
        document.getElementById('equipmentModalTitle').textContent = 'Add Equipment';
        document.getElementById('equipmentForm').action = '{{ route("admin.equipment.store") }}';
        document.getElementById('equipmentMethod').value = 'POST';
        document.getElementById('equipmentLabId').value = labId;
        document.getElementById('equipmentForm').reset();
        document.getElementById('equipmentLabId').value = labId; // Set again after reset
        document.getElementById('deleteEquipmentBtn').style.display = 'none';
        currentEquipmentId = null;
        document.getElementById('equipmentModal').classList.add('active');
    }

    function editEquipment(equipmentId) {
        fetch(`/admin/equipment/${equipmentId}/edit`)
            .then(response => response.json())
            .then(equipment => {
                document.getElementById('equipmentModalTitle').textContent = 'Edit Equipment';
                document.getElementById('equipmentForm').action = `/admin/equipment/${equipmentId}`;
                document.getElementById('equipmentMethod').value = 'PUT';
                document.getElementById('equipmentLabId').value = equipment.lab_id;
                document.getElementById('equipmentCode').value = equipment.equipment_code;
                document.getElementById('equipmentType').value = equipment.type;
                document.getElementById('equipmentStatus').value = equipment.status;
                document.getElementById('equipmentNotes').value = equipment.notes || '';
                document.getElementById('deleteEquipmentBtn').style.display = 'inline-block';
                currentEquipmentId = equipmentId;
                document.getElementById('equipmentModal').classList.add('active');
            });
    }

    function closeEquipmentModal() {
        document.getElementById('equipmentModal').classList.remove('active');
        currentEquipmentId = null;
    }

    function deleteEquipment() {
        if (!currentEquipmentId) return;
        
        if (confirm('Are you sure you want to delete this equipment?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/equipment/${currentEquipmentId}`;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modals when clicking outside
    document.getElementById('labModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeLabModal();
    });

    document.getElementById('equipmentModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeEquipmentModal();
    });
</script>
@endpush