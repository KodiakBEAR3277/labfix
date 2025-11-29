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

        <div class="progress-steps">
            <div class="progress-line" style="width: 0%"></div>
            <div class="step active">
                <div class="step-circle">1</div>
                <div class="step-label">Lab Location</div>
            </div>
            <div class="step">
                <div class="step-circle">2</div>
                <div class="step-label">Equipment</div>
            </div>
            <div class="step">
                <div class="step-circle">3</div>
                <div class="step-label">Problem Type</div>
            </div>
            <div class="step">
                <div class="step-circle">4</div>
                <div class="step-label">Description</div>
            </div>
            <div class="step">
                <div class="step-circle">5</div>
                <div class="step-label">Review</div>
            </div>
        </div>

        <div class="form-container">
            <!-- Step 1: Lab Selection -->
            <div class="form-step active" data-step="1">
                <h2>Select Lab Location</h2>
                <div class="lab-grid">
                    <div class="lab-option" onclick="selectLab(this, 'Computer Lab A')">
                        <div class="lab-icon">💻</div>
                        <div class="lab-name">Computer Lab A</div>
                    </div>
                    <div class="lab-option" onclick="selectLab(this, 'Computer Lab B')">
                        <div class="lab-icon">💻</div>
                        <div class="lab-name">Computer Lab B</div>
                    </div>
                    <div class="lab-option" onclick="selectLab(this, 'Computer Lab C')">
                        <div class="lab-icon">💻</div>
                        <div class="lab-name">Computer Lab C</div>
                    </div>
                    <div class="lab-option" onclick="selectLab(this, 'Multimedia Lab')">
                        <div class="lab-icon">🎨</div>
                        <div class="lab-name">Multimedia Lab</div>
                    </div>
                    <div class="lab-option" onclick="selectLab(this, 'Programming Lab')">
                        <div class="lab-icon">⌨️</div>
                        <div class="lab-name">Programming Lab</div>
                    </div>
                    <div class="lab-option" onclick="selectLab(this, 'Library Computer Area')">
                        <div class="lab-icon">📚</div>
                        <div class="lab-name">Library Area</div>
                    </div>
                </div>
                <div class="button-group">
                    <button class="btn btn-primary" onclick="nextStep()">Next Step</button>
                </div>
            </div>

            <!-- Step 2: Equipment Selection -->
            <div class="form-step" data-step="2">
                <h2>Select Equipment</h2>
                <div class="form-group">
                    <label for="equipment">Equipment/Computer ID</label>
                    <select id="equipment">
                        <option value="">Choose equipment...</option>
                        <option value="PC-01">PC-01</option>
                        <option value="PC-02">PC-02</option>
                        <option value="PC-03">PC-03</option>
                        <option value="PC-04">PC-04</option>
                        <option value="PC-05">PC-05</option>
                    </select>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="prevStep()">Back</button>
                    <button class="btn btn-primary" onclick="nextStep()">Next Step</button>
                </div>
            </div>

            <!-- Step 3: Problem Category -->
            <div class="form-step" data-step="3">
                <h2>What type of problem are you experiencing?</h2>
                <div class="category-grid">
                    <div class="category-option" onclick="selectCategory(this, 'Hardware')">
                        <div class="category-icon">🔧</div>
                        <div class="category-info">
                            <h3>Hardware</h3>
                            <p>Physical components issues</p>
                        </div>
                    </div>
                    <div class="category-option" onclick="selectCategory(this, 'Software')">
                        <div class="category-icon">💾</div>
                        <div class="category-info">
                            <h3>Software</h3>
                            <p>Programs and applications</p>
                        </div>
                    </div>
                    <div class="category-option" onclick="selectCategory(this, 'Network')">
                        <div class="category-icon">🌐</div>
                        <div class="category-info">
                            <h3>Network</h3>
                            <p>Internet and connectivity</p>
                        </div>
                    </div>
                    <div class="category-option" onclick="selectCategory(this, 'Other')">
                        <div class="category-icon">❓</div>
                        <div class="category-info">
                            <h3>Other</h3>
                            <p>Other technical issues</p>
                        </div>
                    </div>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="prevStep()">Back</button>
                    <button class="btn btn-primary" onclick="nextStep()">Next Step</button>
                </div>
            </div>

            <!-- Step 4: Description -->
            <div class="form-step" data-step="4">
                <h2>Describe the Problem</h2>
                <div class="form-group">
                    <label for="title">Issue Title</label>
                    <input type="text" id="title" placeholder="Brief description of the problem">
                </div>
                <div class="form-group">
                    <label for="description">Detailed Description</label>
                    <textarea id="description" placeholder="Please provide as much detail as possible about the issue you're experiencing..."></textarea>
                </div>
                <div class="form-group">
                    <label>Attach Screenshots (Optional)</label>
                    <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                        <input type="file" id="fileInput" accept="image/*" multiple>
                        <div class="upload-icon">📷</div>
                        <p style="color: #9ca3af;">Click to upload screenshots</p>
                        <p style="color: #6b7280; font-size: 0.85rem;">PNG, JPG up to 5MB</p>
                    </div>
                </div>
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="prevStep()">Back</button>
                    <button class="btn btn-primary" onclick="nextStep()">Review & Submit</button>
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
                <div class="button-group">
                    <button class="btn btn-secondary" onclick="prevStep()">Back</button>
                    <button class="btn btn-primary" onclick="submitReport()">Submit Report</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let currentStep = 1;
    const formData = {};

    function nextStep() {
        if (currentStep < 5) {
            document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
            currentStep++;
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
            updateProgress();
            if (currentStep === 5) updateReview();
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
            currentStep--;
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
            updateProgress();
        }
    }

    function updateProgress() {
        const steps = document.querySelectorAll('.step');
        const progressLine = document.querySelector('.progress-line');
        
        steps.forEach((step, index) => {
            if (index < currentStep - 1) {
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (index === currentStep - 1) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active', 'completed');
            }
        });
        
        const progress = ((currentStep - 1) / 4) * 100;
        progressLine.style.width = progress + '%';
    }

    function selectLab(element, lab) {
        document.querySelectorAll('.lab-option').forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        formData.lab = lab;
    }

    function selectCategory(element, category) {
        document.querySelectorAll('.category-option').forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        formData.category = category;
    }

    function updateReview() {
        document.getElementById('reviewLab').textContent = formData.lab || '-';
        document.getElementById('reviewEquipment').textContent = document.getElementById('equipment').value || '-';
        document.getElementById('reviewCategory').textContent = formData.category || '-';
        document.getElementById('reviewTitle').textContent = document.getElementById('title').value || '-';
        document.getElementById('reviewDescription').textContent = document.getElementById('description').value || '-';
    }

    function submitReport() {
        alert('Report submitted successfully! Ticket #' + Math.floor(Math.random() * 1000).toString().padStart(3, '0'));
        window.location.href = "{{ route('user.dashboard') }}";
    }
</script>
@endpush