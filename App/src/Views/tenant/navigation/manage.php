<?php ob_start(); ?>
<?php
$u = current_user();
$displayName = $u['name'] ?? ($u['username'] ?? 'User');
$role = user_role() ?: ($u['role'] ?? '-');
?>

<div class="navigation-management-container">
    <!-- Header -->
    <div class="navigation-header">
        <div>
            <h1 class="navigation-title">🧭 Menu Navigation</h1>
            <p class="text-muted">Kelola menu navigasi untuk tenant: <?= htmlspecialchars($currentTenant['name']) ?></p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showAddMenuModal()">
                <i class="bi bi-plus-circle"></i> Tambah Menu
            </button>
            <button class="btn btn-secondary" onclick="resetToDefault()">
                <i class="bi bi-arrow-clockwise"></i> Reset Default
            </button>
        </div>
    </div>

    <!-- Menu Sections -->
    <div class="menu-sections">
        <?php
        $sections = [
            'main' => 'Utama',
            'transactions' => 'Transaksi',
            'master_data' => 'Data Master',
            'reports' => 'Laporan',
            'system' => 'Sistem',
            'other' => 'Lainnya'
        ];
        
        foreach ($sections as $sectionKey => $sectionTitle):
            $sectionItems = array_filter($menuItems, function($item) use ($sectionKey) {
                return ($item['custom_data']['section'] ?? 'other') === $sectionKey;
            });
            
            if (empty($sectionItems)) continue;
        ?>
        <div class="menu-section">
            <div class="section-header">
                <h3 class="section-title"><?= htmlspecialchars($sectionTitle) ?></h3>
                <div class="section-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="addMenuItemToSection('<?= $sectionKey ?>')">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </div>
            
            <div class="menu-items" data-section="<?= $sectionKey ?>">
                <?php foreach ($sectionItems as $item): ?>
                <div class="menu-item" data-id="<?= $item['id'] ?>" data-order="<?= $item['order'] ?>">
                    <div class="menu-item-content">
                        <div class="menu-item-info">
                            <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                            <span class="menu-title"><?= htmlspecialchars($item['title']) ?></span>
                            <span class="menu-key"><?= htmlspecialchars($item['menu_key']) ?></span>
                        </div>
                        <div class="menu-item-actions">
                            <button class="btn btn-sm btn-outline-secondary" onclick="editMenuItem(<?= $item['id'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-<?= $item['is_active'] ? 'success' : 'warning' ?>" onclick="toggleMenuItem(<?= $item['id'] ?>)">
                                <i class="bi bi-<?= $item['is_active'] ? 'eye' : 'eye-slash' ?>"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMenuItem(<?= $item['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="menu-item-route">
                        <small class="text-muted">Route: <?= htmlspecialchars($item['route'] ?? '#') ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Menu Modal -->
<div class="modal fade" id="menuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="menuModalTitle">Tambah Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="menuForm">
                    <input type="hidden" id="menuId" name="menu_id">
                    
                    <div class="mb-3">
                        <label for="menuKey" class="form-label">Menu Key</label>
                        <input type="text" class="form-control" id="menuKey" name="menu_key" required>
                        <div class="form-text">Unique identifier for the menu item</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menuTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="menuTitle" name="title" required>
                        <div class="form-text">Display title for the menu item</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menuIcon" class="form-label">Icon</label>
                        <input type="text" class="form-control" id="menuIcon" name="icon" value="bi bi-circle">
                        <div class="form-text">Bootstrap Icons class (e.g., bi bi-house)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menuRoute" class="form-label">Route</label>
                        <input type="text" class="form-control" id="menuRoute" name="route" value="#">
                        <div class="form-text">URL path for the menu item</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menuSection" class="form-label">Section</label>
                        <select class="form-control" id="menuSection" name="section">
                            <option value="main">Utama</option>
                            <option value="transactions">Transaksi</option>
                            <option value="master_data">Data Master</option>
                            <option value="reports">Laporan</option>
                            <option value="system">Sistem</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menuOrder" class="form-label">Order</label>
                        <input type="number" class="form-control" id="menuOrder" name="order" min="0" max="999">
                        <div class="form-text">Display order (0-999)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menuPermissions" class="form-label">Permissions</label>
                        <textarea class="form-control" id="menuPermissions" name="permissions" rows="3">[]</textarea>
                        <div class="form-text">JSON array of required permissions (e.g., ["dashboard.view"])</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="menuActive" name="is_active" checked>
                            <label class="form-check-label" for="menuActive">
                                Active
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveMenuItem()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<style>
.navigation-management-container {
    padding: 20px;
    background: #f8f9fa;
    min-height: 100vh;
}

.navigation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.navigation-title {
    font-size: 2rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.menu-sections {
    display: grid;
    gap: 20px;
}

.menu-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #3498db;
    color: white;
}

.section-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.menu-items {
    padding: 0;
}

.menu-item {
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s;
}

.menu-item:hover {
    background-color: #f8f9fa;
}

.menu-item:last-child {
    border-bottom: none;
}

.menu-item-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
}

.menu-item-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.menu-item-info i {
    font-size: 1.2rem;
    color: #3498db;
}

.menu-title {
    font-weight: 500;
    color: #2c3e50;
}

.menu-key {
    font-size: 0.8rem;
    color: #7f8c8d;
    background: #ecf0f1;
    padding: 2px 6px;
    border-radius: 4px;
}

.menu-item-actions {
    display: flex;
    gap: 5px;
}

.menu-item-route {
    padding: 0 20px 10px;
    font-size: 0.8rem;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
}

.sortable-ghost {
    opacity: 0.4;
    background: #f8f9fa;
}

.sortable-drag {
    opacity: 0.9;
}
</style>

<script>
// Initialize sortable for menu items
document.addEventListener('DOMContentLoaded', function() {
    initializeSortable();
});

function initializeSortable() {
    const menuSections = document.querySelectorAll('.menu-items');
    
    menuSections.forEach(section => {
        new Sortable(section, {
            group: 'navigation',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                updateMenuOrder(evt.to.parentElement.dataset.section);
            }
        });
    });
}

function showAddMenuModal(section = 'main') {
    document.getElementById('menuModalTitle').textContent = 'Tambah Menu';
    document.getElementById('menuForm').reset();
    document.getElementById('menuId').value = '';
    document.getElementById('menuSection').value = section;
    
    const modal = new bootstrap.Modal(document.getElementById('menuModal'));
    modal.show();
}

function addMenuItemToSection(section) {
    showAddMenuModal(section);
}

function editMenuItem(menuId) {
    // Fetch menu item data
    fetch(`/navigation/get-menu-item/${menuId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.menu;
                document.getElementById('menuModalTitle').textContent = 'Edit Menu';
                document.getElementById('menuId').value = item.id;
                document.getElementById('menuKey').value = item.menu_key;
                document.getElementById('menuTitle').value = item.title;
                document.getElementById('menuIcon').value = item.icon;
                document.getElementById('menuRoute').value = item.route;
                document.getElementById('menuSection').value = item.custom_data.section;
                document.getElementById('menuOrder').value = item.order;
                document.getElementById('menuPermissions').value = JSON.stringify(item.permissions || []);
                document.getElementById('menuActive').checked = item.is_active;
                
                const modal = new bootstrap.Modal(document.getElementById('menuModal'));
                modal.show();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching menu item');
        });
}

function saveMenuItem() {
    const form = document.getElementById('menuForm');
    const formData = new FormData(form);
    
    // Convert JSON fields
    const permissions = document.getElementById('menuPermissions').value;
    const customData = {
        section: document.getElementById('menuSection').value
    };
    
    const data = {
        menu_id: formData.get('menu_id') || '',
        menu_key: formData.get('menu_key'),
        title: formData.get('title'),
        icon: formData.get('icon'),
        route: formData.get('route'),
        order: formData.get('order'),
        permissions: JSON.parse(permissions || '[]'),
        custom_data: JSON.stringify(customData),
        is_active: formData.has('is_active')
    };
    
    const url = data.menu_id ? '/navigation/update-menu-item' : '/navigation/add-menu-item';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving menu item');
    });
}

function toggleMenuItem(menuId) {
    if (confirm('Toggle menu item status?')) {
        fetch(`/navigation/toggle-menu-item/${menuId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error toggling menu item');
            });
    }
}

function deleteMenuItem(menuId) {
    if (confirm('Delete this menu item? This action cannot be undone.')) {
        fetch(`/navigation/remove-menu-item/${menuId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting menu item');
            });
    }
}

function updateMenuOrder(section) {
    const sectionElement = document.querySelector(`.menu-items[data-section="${section}"]`);
    const menuItems = sectionElement.querySelectorAll('.menu-item');
    const order = {};
    
    menuItems.forEach((item, index) => {
        const id = item.dataset.id;
        order[id] = index;
    });
    
    fetch('/navigation/reorder-menu', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(order)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error updating menu order:', data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function resetToDefault() {
    if (confirm('Reset menu to default configuration? This will remove all custom menu items.')) {
        fetch('/navigation/setup-tenant-menu', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `tenant_id=<?= $currentTenant['id'] ?>`
        })
        .then(response => response.text())
        .then(() => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error resetting menu');
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include view_path('layout_dashboard');
echo $content;
?>
