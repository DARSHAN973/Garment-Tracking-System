<?php
$pageTitle = 'Create Method Analysis';
require_once '../auth/session_check.php';
require_once '../utils/Database.php';
require_once '../utils/Calculator.php';

// Permission check removed for single user system;

$db = new DatabaseHelper();
$calculator = new Calculator();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $styleId = intval($_POST['style_id']);
        $operationName = sanitizeInput($_POST['operation_name']);
        $methodName = sanitizeInput($_POST['method_name']);
        $description = sanitizeInput($_POST['description']);
        $elements = json_decode($_POST['elements'], true);
        
        if (empty($styleId) || empty($operationName) || empty($elements)) {
            $message = 'Style, operation name, and elements are required.';
            $messageType = 'error';
        } else {
            try {
                $db->beginTransaction();
                
                // Calculate total standard time
                $totalTime = array_sum(array_column($elements, 'standard_time'));
                
                // Insert Method Analysis record
                $methodId = $db->insert('method_analysis', [
                    'style_id' => $styleId,
                    'operation_name' => $operationName,
                    'method_name' => $methodName,
                    'description' => $description,
                    'total_standard_time' => $totalTime,
                    'status' => 'DRAFT'
                ]);
                
                if ($methodId) {
                    // Insert elements
                    foreach ($elements as $index => $element) {
                        $db->insert('method_analysis_details', [
                            'method_analysis_id' => $methodId,
                            'element_id' => $element['element_id'],
                            'sequence_no' => $index + 1,
                            'description' => $element['description'],
                            'standard_time' => $element['standard_time'],
                            'frequency' => $element['frequency'],
                            'total_time' => $element['total_time']
                        ]);
                    }
                    
                    $db->commit();
                    logActivity('method_analysis', $methodId, 'CREATE');
                    header('Location: method_detail.php?id=' . $methodId);
                    exit;
                } else {
                    throw new Exception('Failed to create Method Analysis');
                }
            } catch (Exception $e) {
                $db->rollback();
                $message = 'Error creating Method Analysis: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get data for dropdowns
$styles = $db->query("SELECT style_id, style_code, description FROM styles WHERE is_active = 1 ORDER BY style_code");
$gsdElements = $db->query("
    SELECT element_id, element_code, element_name, category, basic_time, 
           conditional_time_a, conditional_time_b, unit 
    FROM gsd_elements 
    WHERE is_active = 1 
    ORDER BY category, element_name
");

// Group GSD elements by category
$elementsByCategory = [];
foreach ($gsdElements as $element) {
    $category = $element['category'] ?: 'Other';
    $elementsByCategory[$category][] = $element;
}

include '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-6xl mx-auto">
            
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Create Method Analysis</h1>
                        <p class="text-gray-600 mt-2">Document work methods using GSD elements and time studies</p>
                    </div>
                    <a href="method_list.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        ← Back to List
                    </a>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'error' ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200'; ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 <?php echo $messageType === 'error' ? 'text-red-400' : 'text-green-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $messageType === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M5 13l4 4L19 7'; ?>"></path>
                    </svg>
                    <p class="<?php echo $messageType === 'error' ? 'text-red-700' : 'text-green-700'; ?>"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" id="methodForm">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="elements" id="elementsData">
                
                <!-- Basic Information -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Style *</label>
                            <select name="style_id" id="styleSelect" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">Select a style</option>
                                <?php foreach ($styles as $style): ?>
                                <option value="<?php echo $style['style_id']; ?>">
                                    <?php echo htmlspecialchars($style['style_code']); ?>
                                    <?php if ($style['description']): ?>
                                     - <?php echo htmlspecialchars($style['description']); ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Operation Name *</label>
                            <input type="text" name="operation_name" required maxlength="100" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="e.g., Sleeve Attach, Collar Setting">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Method Name</label>
                            <input type="text" name="method_name" maxlength="100" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="e.g., Standard Method v1.0">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="2" maxlength="500"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                      placeholder="Method description and notes"></textarea>
                        </div>
                    </div>
                </div>

                <!-- GSD Elements Section -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">GSD Elements Sequence</h3>
                        <button type="button" onclick="addElement()" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Element
                        </button>
                    </div>
                    
                    <!-- Elements Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full" id="elementsTable">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">#</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Element</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Frequency</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Time (TMU)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Total TMU</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-20">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="elementsBody">
                                <!-- Elements will be added dynamically -->
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 font-medium">
                                    <td colspan="5" class="px-4 py-2 text-right">Total Standard Time:</td>
                                    <td class="px-4 py-2" id="totalTime">0.000 TMU</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <div id="emptyStateElements" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <p>No GSD elements added yet. Click "Add Element" to start building your method analysis.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="method_list.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" id="saveButton" disabled 
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Create Method Analysis
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Element Modal -->
<div id="addElementModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-2/3 max-w-3xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add GSD Element</h3>
            <form id="addElementForm">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Element Category</label>
                        <select id="elementCategory" onchange="filterElements()" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">All Categories</option>
                            <?php foreach (array_keys($elementsByCategory) as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">GSD Element *</label>
                        <select id="elementSelect" required onchange="loadElementDetails()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">Select element</option>
                            <?php foreach ($elementsByCategory as $category => $categoryElements): ?>
                            <optgroup label="<?php echo htmlspecialchars($category); ?>">
                                <?php foreach ($categoryElements as $element): ?>
                                <option value="<?php echo $element['element_id']; ?>" 
                                        data-element='<?php echo htmlspecialchars(json_encode($element)); ?>'>
                                    <?php echo htmlspecialchars($element['element_code'] . ' - ' . $element['element_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-span-2" id="elementInfo" style="display: none;">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-2">Element Details</h4>
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <label class="text-gray-600">Basic Time:</label>
                                    <div id="basicTime" class="font-medium">-</div>
                                </div>
                                <div>
                                    <label class="text-gray-600">Conditional A:</label>
                                    <div id="conditionalA" class="font-medium">-</div>
                                </div>
                                <div>
                                    <label class="text-gray-600">Conditional B:</label>
                                    <div id="conditionalB" class="font-medium">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Frequency *</label>
                        <input type="number" id="frequency" step="0.1" min="0.1" value="1" required 
                               onchange="calculateElementTime()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="1.0">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Time Selection</label>
                        <select id="timeType" onchange="calculateElementTime()" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="basic">Basic Time</option>
                            <option value="conditional_a">Conditional A</option>
                            <option value="conditional_b">Conditional B</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Standard Time (TMU)</label>
                        <input type="number" id="standardTime" step="0.001" min="0" readonly 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Total Time (TMU)</label>
                        <input type="number" id="totalElementTime" step="0.001" min="0" readonly 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="elementDescription" rows="2" maxlength="255"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                  placeholder="Optional description for this element instance"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeAddElementModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Add Element
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let elements = [];
let elementCounter = 0;

const elementsData = <?php echo json_encode($elementsByCategory); ?>;

function addElement() {
    document.getElementById('addElementModal').classList.remove('hidden');
}

function closeAddElementModal() {
    document.getElementById('addElementModal').classList.add('hidden');
    document.getElementById('addElementForm').reset();
    document.getElementById('elementInfo').style.display = 'none';
}

function filterElements() {
    const category = document.getElementById('elementCategory').value;
    const elementSelect = document.getElementById('elementSelect');
    
    // Clear current options except the first one
    elementSelect.innerHTML = '<option value="">Select element</option>';
    
    if (category) {
        const categoryElements = elementsData[category] || [];
        categoryElements.forEach(elem => {
            const option = document.createElement('option');
            option.value = elem.element_id;
            option.textContent = `${elem.element_code} - ${elem.element_name}`;
            option.setAttribute('data-element', JSON.stringify(elem));
            elementSelect.appendChild(option);
        });
    } else {
        // Show all elements grouped
        Object.entries(elementsData).forEach(([cat, elems]) => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = cat;
            elems.forEach(elem => {
                const option = document.createElement('option');
                option.value = elem.element_id;
                option.textContent = `${elem.element_code} - ${elem.element_name}`;
                option.setAttribute('data-element', JSON.stringify(elem));
                optgroup.appendChild(option);
            });
            elementSelect.appendChild(optgroup);
        });
    }
}

function loadElementDetails() {
    const select = document.getElementById('elementSelect');
    const selectedOption = select.selectedOptions[0];
    
    if (selectedOption && selectedOption.value) {
        const element = JSON.parse(selectedOption.getAttribute('data-element'));
        
        document.getElementById('basicTime').textContent = element.basic_time + ' TMU';
        document.getElementById('conditionalA').textContent = (element.conditional_time_a || '-') + (element.conditional_time_a ? ' TMU' : '');
        document.getElementById('conditionalB').textContent = (element.conditional_time_b || '-') + (element.conditional_time_b ? ' TMU' : '');
        
        document.getElementById('elementInfo').style.display = 'block';
        calculateElementTime();
    } else {
        document.getElementById('elementInfo').style.display = 'none';
    }
}

function calculateElementTime() {
    const select = document.getElementById('elementSelect');
    const selectedOption = select.selectedOptions[0];
    
    if (selectedOption && selectedOption.value) {
        const element = JSON.parse(selectedOption.getAttribute('data-element'));
        const frequency = parseFloat(document.getElementById('frequency').value) || 1;
        const timeType = document.getElementById('timeType').value;
        
        let standardTime = 0;
        switch (timeType) {
            case 'basic':
                standardTime = parseFloat(element.basic_time) || 0;
                break;
            case 'conditional_a':
                standardTime = parseFloat(element.conditional_time_a) || 0;
                break;
            case 'conditional_b':
                standardTime = parseFloat(element.conditional_time_b) || 0;
                break;
        }
        
        const totalTime = standardTime * frequency;
        
        document.getElementById('standardTime').value = standardTime.toFixed(3);
        document.getElementById('totalElementTime').value = totalTime.toFixed(3);
    }
}

document.getElementById('addElementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const elementId = document.getElementById('elementSelect').value;
    const frequency = parseFloat(document.getElementById('frequency').value) || 1;
    const standardTime = parseFloat(document.getElementById('standardTime').value) || 0;
    const totalTime = parseFloat(document.getElementById('totalElementTime').value) || 0;
    const description = document.getElementById('elementDescription').value;
    
    if (!elementId || standardTime <= 0) {
        alert('Please select an element and ensure time is calculated');
        return;
    }
    
    // Get selected element details
    const selectedOption = document.getElementById('elementSelect').selectedOptions[0];
    const elementData = JSON.parse(selectedOption.getAttribute('data-element'));
    
    const element = {
        id: ++elementCounter,
        element_id: elementId,
        element_code: elementData.element_code,
        element_name: elementData.element_name,
        category: elementData.category,
        frequency: frequency,
        standard_time: standardTime,
        total_time: totalTime,
        description: description
    };
    
    elements.push(element);
    renderElements();
    closeAddElementModal();
});

function renderElements() {
    const tbody = document.getElementById('elementsBody');
    const emptyState = document.getElementById('emptyStateElements');
    
    if (elements.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('hidden');
        updateTotals();
        return;
    }
    
    emptyState.classList.add('hidden');
    
    tbody.innerHTML = elements.map((elem, index) => `
        <tr class="border-b border-gray-200">
            <td class="px-4 py-2 text-sm text-gray-500">${index + 1}</td>
            <td class="px-4 py-2">
                <div class="text-sm font-medium text-gray-900">${elem.element_code}</div>
                <div class="text-xs text-gray-500">${elem.element_name}</div>
            </td>
            <td class="px-4 py-2">
                <span class="text-xs px-2 py-1 bg-gray-100 text-gray-800 rounded-full">${elem.category}</span>
            </td>
            <td class="px-4 py-2">
                <input type="number" step="0.1" min="0.1" value="${elem.frequency}" 
                       onchange="updateElementFrequency(${elem.id}, this.value)"
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500">
            </td>
            <td class="px-4 py-2">
                <span class="text-sm font-medium text-gray-900">${elem.standard_time.toFixed(3)}</span>
            </td>
            <td class="px-4 py-2">
                <span class="text-sm font-medium text-indigo-600">${elem.total_time.toFixed(3)}</span>
            </td>
            <td class="px-4 py-2">
                <input type="text" value="${elem.description || ''}" maxlength="255"
                       onchange="updateElementDescription(${elem.id}, this.value)"
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-indigo-500"
                       placeholder="Optional description">
            </td>
            <td class="px-4 py-2 text-center">
                <div class="flex justify-center space-x-1">
                    ${index > 0 ? `<button type="button" onclick="moveElement(${elem.id}, 'up')" class="text-indigo-600 hover:text-indigo-800" title="Move Up">↑</button>` : ''}
                    ${index < elements.length - 1 ? `<button type="button" onclick="moveElement(${elem.id}, 'down')" class="text-indigo-600 hover:text-indigo-800" title="Move Down">↓</button>` : ''}
                    <button type="button" onclick="removeElement(${elem.id})" class="text-red-600 hover:text-red-800 ml-2" title="Remove">×</button>
                </div>
            </td>
        </tr>
    `).join('');
    
    updateTotals();
}

function updateElementFrequency(id, frequency) {
    const element = elements.find(elem => elem.id === id);
    if (element) {
        element.frequency = parseFloat(frequency) || 1;
        element.total_time = element.standard_time * element.frequency;
        renderElements();
    }
}

function updateElementDescription(id, description) {
    const element = elements.find(elem => elem.id === id);
    if (element) {
        element.description = description;
    }
}

function removeElement(id) {
    elements = elements.filter(elem => elem.id !== id);
    renderElements();
}

function moveElement(id, direction) {
    const index = elements.findIndex(elem => elem.id === id);
    if (direction === 'up' && index > 0) {
        [elements[index], elements[index - 1]] = [elements[index - 1], elements[index]];
    } else if (direction === 'down' && index < elements.length - 1) {
        [elements[index], elements[index + 1]] = [elements[index + 1], elements[index]];
    }
    renderElements();
}

function updateTotals() {
    const totalTime = elements.reduce((sum, elem) => sum + elem.total_time, 0);
    
    document.getElementById('totalTime').textContent = totalTime.toFixed(3) + ' TMU';
    
    // Update form data
    document.getElementById('elementsData').value = JSON.stringify(elements);
    
    // Enable/disable save button
    const saveButton = document.getElementById('saveButton');
    const styleSelected = document.getElementById('styleSelect').value;
    const operationName = document.querySelector('input[name="operation_name"]').value;
    saveButton.disabled = !styleSelected || !operationName.trim() || elements.length === 0;
}

document.getElementById('styleSelect').addEventListener('change', updateTotals);
document.querySelector('input[name="operation_name"]').addEventListener('input', updateTotals);

// Close modal on outside click
document.addEventListener('click', function(event) {
    if (event.target.id === 'addElementModal') {
        closeAddElementModal();
    }
});

// Initialize empty state
renderElements();
</script>

<?php include '../includes/footer.php'; ?>