<?php
require_once 'includes/auth.php';
requireLogin();

$user = currentUser();
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $user['id'];
$profileUser = getUserById($profileId);

if (!$profileUser) {
    header('Location: dashboard.php');
    exit();
}

$isMe = $profileUser['id'] === $user['id'];
$isAdmin = $user['is_admin'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isMe) {
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);
    $bio = sanitize($_POST['bio']);
    
    // Handle avatar upload
    $avatar = $profileUser['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadAvatar($_FILES['avatar']);
        if ($uploadResult['success']) {
            $avatar = $uploadResult['filename'];
            // Delete old avatar if not default
            if ($profileUser['avatar'] !== 'default.png') {
                @unlink(__DIR__ . "/uploads/avatars/{$profileUser['avatar']}");
            }
        }
    }
    
    // Update profile
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ?, avatar = ? WHERE id = ?");
    if ($stmt->execute([$firstName, $lastName, $bio, $avatar, $user['id']])) {
        header("Location: profile.php?id={$profileUser['id']}");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profileUser['username']); ?>  - Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Profile header -->
                <div class="bg-indigo-600 p-6 text-white">
                    <div class="flex items-center space-x-6">
                        <div class="relative">
                            <img src="uploads/avatars/<?php echo $profileUser['avatar']; ?>" alt="<?php echo htmlspecialchars($profileUser['username']); ?>" class="w-24 h-24 rounded-full border-4 border-white">
                            <span class="absolute bottom-0 right-0 block h-5 w-5 rounded-full <?php echo $profileUser['status'] === 'online' ? 'bg-green-500' : 'bg-gray-400'; ?> ring-2 ring-white"></span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">
                                <?php echo htmlspecialchars($profileUser['first_name'] . ' ' . $profileUser['last_name']); ?>
                            </h1>
                            <p class="text-indigo-100">@<?php echo htmlspecialchars($profileUser['username']); ?></p>
                            <p class="mt-2"><?php echo htmlspecialchars($profileUser['bio']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Profile content -->
                <div class="p-6">
                    <?php if ($isMe || $isAdmin): ?>
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profileUser['first_name']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profileUser['last_name']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                                <textarea id="bio" name="bio" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($profileUser['bio']); ?></textarea>
                            </div>
                            
                            <div>
                                <label for="avatar" class="block text-sm font-medium text-gray-700">Profile Picture</label>
                                <input type="file" id="avatar" name="avatar" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">First Name</p>
                                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($profileUser['first_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Last Name</p>
                                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($profileUser['last_name']); ?></p>
                                </div>
                            </div>
                            
                            <div>
                                <p class="text-sm font-medium text-gray-500">Bio</p>
                                <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($profileUser['bio']); ?></p>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="chat.php?user=<?php echo $profileUser['id']; ?>" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Message
                                </a>
                                <button class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="far fa-heart"></i> Like
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- User blogs/notes section -->
            <div class="mt-8">
                <h2 class="text-xl font-bold mb-4"><?php echo $isMe ? 'My Notes' : htmlspecialchars($profileUser['first_name']) . "'s Notes"; ?></h2>
                
                <?php if ($isMe): ?>
                    <div class="mb-4">
                        <button onclick="document.getElementById('new-note-modal').classList.remove('hidden')" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-plus mr-2"></i> New Note
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="notes-container">
                    <!-- Notes will be loaded here via AJAX -->
                </div>
                
                <div class="mt-4 text-center">
                    <button id="load-more-notes" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Load More
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New note modal -->
    <div id="new-note-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Create New Note</h3>
                <button onclick="document.getElementById('new-note-modal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="new-note-form" class="space-y-4">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                
                <div>
                    <label for="note-title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" id="note-title" name="title" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div>
                    <label for="note-content" class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea id="note-content" name="content" rows="5" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                
                <div class="flex items-center">
                    <input id="note-public" name="is_public" type="checkbox" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="note-public" class="ml-2 block text-sm text-gray-700">Public (visible to others)</label>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('new-note-modal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load notes via AJAX
        let currentPage = 1;
        const notesContainer = document.getElementById('notes-container');
        const loadMoreBtn = document.getElementById('load-more-notes');
        
        function loadNotes(page = 1) {
            fetch(`api/blog.php?user_id=<?php echo $profileUser['id']; ?>&page=${page}`)
                .then(response => response.json())
                .then(notes => {
                    if (notes.length > 0) {
                        notes.forEach(note => {
                            const noteElement = document.createElement('div');
                            noteElement.className = 'bg-white rounded-lg shadow-md overflow-hidden';
                            noteElement.innerHTML = `
                                <div class="p-4">
                                    <h3 class="font-medium text-lg mb-2">${note.title}</h3>
                                    <p class="text-gray-600 mb-4 line-clamp-3">${note.content}</p>
                                    <div class="flex justify-between items-center text-sm text-gray-500">
                                        <span>${new Date(note.created_at).toLocaleDateString()}</span>
                                        <div class="flex space-x-2">
                                            <button class="text-indigo-600 hover:text-indigo-800" onclick="viewNote(${note.id})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($isMe || $isAdmin): ?>
                                                <button class="text-blue-600 hover:text-blue-800" onclick="editNote(${note.id})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="text-red-600 hover:text-red-800" onclick="deleteNote(${note.id})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            `;
                            notesContainer.appendChild(noteElement);
                        });
                        
                        if (notes.length >= 6) { // Assuming 6 notes per page
                            loadMoreBtn.classList.remove('hidden');
                        } else {
                            loadMoreBtn.classList.add('hidden');
                        }
                    } else if (page === 1) {
                        notesContainer.innerHTML = '<p class="text-gray-500 col-span-3 text-center py-8">No notes found.</p>';
                        loadMoreBtn.classList.add('hidden');
                    } else {
                        loadMoreBtn.classList.add('hidden');
                    }
                });
        }
        
        // Handle new note form submission
        document.getElementById('new-note-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                title: formData.get('title'),
                content: formData.get('content'),
                is_public: formData.get('is_public') === 'on',
                user_id: formData.get('user_id')
            };
            
            fetch('api/blog.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(note => {
                if (note.id) {
                    document.getElementById('new-note-modal').classList.add('hidden');
                    this.reset();
                    notesContainer.innerHTML = ''; // Clear existing notes
                    currentPage = 1;
                    loadNotes(currentPage);
                }
            });
        });
        
        // Load more notes
        loadMoreBtn.addEventListener('click', function() {
            currentPage++;
            loadNotes(currentPage);
        });
        
        // Initial load
        loadNotes(currentPage);
        
        // Note functions
        function viewNote(id) {
            // Implement view note functionality
            console.log('View note', id);
        }
        
        function editNote(id) {
            // Implement edit note functionality
            console.log('Edit note', id);
        }
        
        function deleteNote(id) {
            if (confirm('Are you sure you want to delete this note?')) {
                fetch(`api/blog.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        notesContainer.innerHTML = ''; // Clear existing notes
                        currentPage = 1;
                        loadNotes(currentPage);
                    }
                });
            }
        }
    </script>
</body>
</html>
