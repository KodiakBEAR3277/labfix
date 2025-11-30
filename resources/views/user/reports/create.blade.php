@extends('layouts.app')

@section('title', 'Report New Issue')

@section('navigation')
    <x-nav.user />
@endsection

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Report New Issue</h1>
            <p>Help us fix your problem by providing detailed information</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #ef4444;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="progress-steps">
            <div class="progress-line" id="progressLine"></div>
            <div class="step active" data-step="1">
                <div class="step-circle">1</div>
                <div class="step-label">Lab Location</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-circle">2</div>
                <div class="step-label">Equipment</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-circle">3</div>
                <div class="step-label">Problem Type</div>
            </div>
            <div class="step" data-step="4">
                <div class="step-circle">4</div>
                <div class="step-label">Description</div>
            </div>
            <div class="step" data-step="5">
                <div class="step-circle">5</div>
                <div class="step-label">Review</div>
            </div>
        </div>

        <form action="{{ route('user.reports.store') }}" method="POST" enctype="multipart/form-data" id="reportForm">
            @csrf
            
            <div class="form-container">
                <!-- Step 1: Lab Selection -->
                <div class="form-step active" data-step="1">
                    <h2>Select Lab Location</h2>
                    <div class="lab-grid">
                        <label class="lab-option">
                            <input type="radio" name="lab_location" value="Computer Lab A" style="display: none;" required {{ old('lab_location') == 'Computer Lab A' ? 'checked' : '' }}>
                            <div class="lab-icon">💻</div>
                            <div class="lab-name">Computer Lab A</div>
                        </label>
                        <label class="lab-option">
                            <input type="radio" name="lab_location" value="Computer Lab B" style="display: none;" {{ old('lab_location') == 'Computer Lab B' ? 'checked' : '' }}>
                            <div class="lab-icon">💻</div>
                            <div class="lab-name">Computer Lab B</div>
                        </label>
                        <label class="lab-option">
                            <input type="radio" name="lab_location" value="Computer Lab C" style="display: none;" {{ old('lab_location') == 'Computer Lab C' ? 'checked' : '' }}>
                            <div class="lab-icon">💻</div>
                            <div class="lab-name">Computer Lab C</div>
                        </label>
                        <label class="lab-option">
                            <input type="radio" name="lab_location" value="Multimedia Lab" style="display: none;" {{ old('lab_location') == 'Multimedia Lab' ? 'checked' : '' }}>
                            <div class="lab-icon">🎨</div>
                            <div class="lab-name">Multimedia Lab</div>
                        </label>
                        <label class="lab-option">
                            <input type="radio" name="lab_location" value="Programming Lab" style="display: none;" {{ old('lab_location') == 'Programming Lab' ? 'checked' : '' }}>
                            <div class="lab-icon">⌨️</div>
                            <div class="lab-name">Programming Lab</div>
                        </label>
                        <label class="lab-option">
                            <input type="radio" name="lab_location" value="Library Computer Area" style="display: none;" {{ old('lab_location') == 'Library Computer Area' ? 'checked' : '' }}>
                            <div class="lab-icon">📚</div>
                            <div class="lab-name">Library Area</div>
                        </label>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn btn-primary" onclick="nextStep()">Next Step</button>
                    </div>
                </div>

                <!-- Step 2: Equipment Selection -->
                <div class="form-step" data-step="2">
                    <h2>Select Equipment</h2>
                    <div class="form-group">
                        <label for="equipment">Equipment/Computer ID (Optional)</label>
                        <select name="equipment_id" id="equipment">
                            <option value="">General lab issue / Don't know</option>
                            @for($i = 1; $i <= 25; $i++)
                                <option value="PC-{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ old('equipment_id') == 'PC-'.str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                    PC-{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                </option>
                            @endfor
                        </select>
                        <p class="help-text">Select the specific equipment if you know it, or leave as "General lab issue"</p>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">Back</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">Next Step</button>
                    </div>
                </div>

                <!-- Step 3: Problem Category -->
                <div class="form-step" data-step="3">
                    <h2>What type of problem are you experiencing?</h2>
                    <div class="category-grid">
                        <label class="category-option">
                            <input type="radio" name="category" value="hardware" style="display: none;" required {{ old('category') == 'hardware' ? 'checked' : '' }}>
                            <div class="category-icon">🔧</div>
                            <div class="category-info">
                                <h3>Hardware</h3>
                                <p>Physical components issues</p>
                            </div>
                        </label>
                        <label class="category-option">
                            <input type="radio" name="category" value="software" style="display: none;" {{ old('category') == 'software' ? 'checked' : '' }}>
                            <div class="category-icon">💾</div>
                            <div class="category-info">
                                <h3>Software</h3>
                                <p>Programs and applications</p>
                            </div>
                        </label>
                        <label class="category-option">
                            <input type="radio" name="category" value="network" style="display: none;" {{ old('category') == 'network' ? 'checked' : '' }}>
                            <div class="category-icon">🌐</div>
                            <div class="category-info">
                                <h3>Network</h3>
                                <p>Internet and connectivity</p>
                            </div>
                        </label>
                        <label class="category-option">
                            <input type="radio" name="category" value="other" style="display: none;" {{ old('category') == 'other' ? 'checked' : '' }}>
                            <div class="category-icon">❓</div>
                            <div class="category-info">
                                <h3>Other</h3>
                                <p>Other technical issues</p>
                            </div>
                        </label>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">Back</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">Next Step</button>
                    </div>
                </div>

                <!-- Step 4: Description -->
                <div class="form-step" data-step="4">
                    <h2>Describe the Problem</h2>
                    <div class="form-group">
                        <label for="title">Issue Title *</label>
                        <input type="text" name="title" id="title" placeholder="Brief description of the problem" required value="{{ old('title') }}">
                    </div>
                    <div class="form-group">
                        <label for="description">Detailed Description *</label>
                        <textarea name="description" id="description" placeholder="Please provide as much detail as possible about the issue you're experiencing..." required>{{ old('description') }}</textarea>
                        <p class="help-text">Minimum 10 characters. Include any error messages, what you were doing when the issue occurred, etc.</p>
                    </div>
                    <div class="form-group">
                        <label>Attach Screenshots (Optional)</label>
                        <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                            <input type="file" name="attachments[]" id="fileInput" accept="image/*,application/pdf" multiple>
                            <div class="upload-icon">📷</div>
                            <p style="color: #9ca3af;">Click to upload screenshots or documents</p>
                            <p style="color: #6b7280; font-size: 0.85rem;">PNG, JPG, PDF up to 5MB each</p>
                        </div>
                        <div id="fileList" style="margin-top: 1rem;"></div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">Back</button>
                        <button type="button" class="btn btn-primary" onclick="nextStep()">Review & Submit</button>
                    </div>
                </div>

                <!-- Step 5: Review -->
                <div class="form-step" data-step="5">
                    <h2>Review Your Report</h2>
                    <div class="review-item">
                        <div class="review-label">Lab Location</div>
                        <div class="review-value" id="reviewLab">-</div>
                    </div>
                    <div class="review-item">
                        <div class="review-label">Equipment</div>
                        <div class="review-value" id="reviewEquipment">-</div>
                    </div>
                    <div class="review-item">
                        <div class="review-label">Problem Category</div>
                        <div class="review-value" id="reviewCategory">-</div>
                    </div>
                    <div class="review-item">
                        <div class="review-label">Issue Title</div>
                        <div class="review-value" id="reviewTitle">-</div>
                    </div>
                    <div class="review-item">
                        <div class="review-label">Description</div>
                        <div class="review-value" id="reviewDescription">-</div>
                    </div>
                    <div class="review-item">
                        <div class="review-label">Attachments</div>
                        <div class="review-value" id="reviewAttachments">None</div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="prevStep()">Back</button>
                        <button type="submit" class="btn btn-primary">Submit Report</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    let currentStep = 1;

    function nextStep() {
        // Validate current step
        const currentStepElement = document.querySelector(`[data-step="${currentStep}"].form-step`);
        const requiredInputs = currentStepElement.querySelectorAll('[required]');
        let isValid = true;

        requiredInputs.forEach(input => {
            if (!input.value && input.type !== 'radio') {
                isValid = false;
                input.style.borderColor = '#ef4444';
            } else if (input.type === 'radio') {
                const radioGroup = document.querySelectorAll(`input[name="${input.name}"]`);
                const isChecked = Array.from(radioGroup).some(radio => radio.checked);
                if (!isChecked) {
                    isValid = false;
                }
            } else {
                input.style.borderColor = '';
            }
        });

        if (!isValid) {
            alert('Please fill in all required fields before proceeding.');
            return;
        }

        if (currentStep < 5) {
            document.querySelector(`[data-step="${currentStep}"].form-step`).classList.remove('active');
            currentStep++;
            document.querySelector(`[data-step="${currentStep}"].form-step`).classList.add('active');
            updateProgress();
            if (currentStep === 5) updateReview();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            document.querySelector(`[data-step="${currentStep}"].form-step`).classList.remove('active');
            currentStep--;
            document.querySelector(`[data-step="${currentStep}"].form-step`).classList.add('active');
            updateProgress();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function updateProgress() {
        const steps = document.querySelectorAll('.progress-steps .step');
        const progressLine = document.getElementById('progressLine');
        
        steps.forEach((step, index) => {
            const stepNum = index + 1;
            if (stepNum < currentStep) {
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (stepNum === currentStep) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active', 'completed');
            }
        });
        
        const progress = ((currentStep - 1) / 4) * 100;
        progressLine.style.width = progress + '%';
    }

    // Lab selection
    document.querySelectorAll('.lab-option input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.lab-option').forEach(opt => opt.classList.remove('selected'));
            if (this.checked) {
                this.closest('.lab-option').classList.add('selected');
            }
        });
        // Trigger on page load if already selected
        if (radio.checked) {
            radio.closest('.lab-option').classList.add('selected');
        }
    });

    // Category selection
    document.querySelectorAll('.category-option input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.category-option').forEach(opt => opt.classList.remove('selected'));
            if (this.checked) {
                this.closest('.category-option').classList.add('selected');
            }
        });
        // Trigger on page load if already selected
        if (radio.checked) {
            radio.closest('.category-option').classList.add('selected');
        }
    });

    // File input display
    document.getElementById('fileInput').addEventListener('change', function() {
        const fileList = document.getElementById('fileList');
        fileList.innerHTML = '';
        
        if (this.files.length > 0) {
            const ul = document.createElement('ul');
            ul.style.listStyle = 'none';
            ul.style.padding = '0';
            
            Array.from(this.files).forEach(file => {
                const li = document.createElement('li');
                li.style.padding = '0.5rem';
                li.style.background = 'rgba(45, 212, 191, 0.1)';
                li.style.marginBottom = '0.5rem';
                li.style.borderRadius = '6px';
                li.style.color = '#2dd4bf';
                li.textContent = `📎 ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                ul.appendChild(li);
            });
            
            fileList.appendChild(ul);
        }
    });

    function updateReview() {
        const lab = document.querySelector('input[name="lab_location"]:checked')?.value || '-';
        const equipment = document.getElementById('equipment').value || 'General lab issue';
        const category = document.querySelector('input[name="category"]:checked')?.value || '-';
        const title = document.getElementById('title').value || '-';
        const description = document.getElementById('description').value || '-';
        const files = document.getElementById('fileInput').files;

        document.getElementById('reviewLab').textContent = lab;
        document.getElementById('reviewEquipment').textContent = equipment;
        document.getElementById('reviewCategory').textContent = category.charAt(0).toUpperCase() + category.slice(1);
        document.getElementById('reviewTitle').textContent = title;
        document.getElementById('reviewDescription').textContent = description;
        
        if (files.length > 0) {
            document.getElementById('reviewAttachments').textContent = `${files.length} file(s) attached`;
        } else {
            document.getElementById('reviewAttachments').textContent = 'None';
        }
    }

    // Initialize progress on page load
    updateProgress();
</script>
@endpush