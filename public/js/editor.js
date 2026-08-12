let easyMDE;
let editingId = null;

async function initEditor() {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    const form = document.getElementById('blogForm');
    const titleInput = document.getElementById('titleInput');
    const markdownTextarea = document.getElementById('markdownEditor');

    // Initialize EasyMDE
    easyMDE = new EasyMDE({ element: markdownTextarea });

    // If editing, load data
    if (id) {
        try {
            const blog = await fetchAPI(`blog.php?action=single&id=${id}`);
            // Check ownership
            const user = getCurrentUser();
            if (!user || user.id !== blog.user_id) {
                alert('You do not have permission to edit this blog.');
                window.location.href = 'index.html';
                return;
            }
            editingId = id;
            document.getElementById('editorTitle').textContent = 'Edit Blog';
            titleInput.value = blog.title;
            easyMDE.value(blog.content);
        } catch (err) {
            alert('Failed to load blog: ' + err.message);
            window.location.href = 'index.html';
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const title = titleInput.value.trim();
        const content = easyMDE.value().trim();
        if (!title || !content) {
            alert('Please fill in both title and content.');
            return;
        }

        try {
            let endpoint = 'blog.php?action=create';
            let method = 'POST';
            if (editingId) {
                endpoint = `blog.php?id=${editingId}`;
                method = 'PUT';
            }
            await fetchAPI(endpoint, {
                method: method,
                body: { title, content }
            });
            window.location.href = 'index.html';
        } catch (err) {
            alert('Save failed: ' + err.message);
        }
    });

    document.getElementById('cancelBtn').addEventListener('click', () => {
        window.location.href = 'index.html';
    });
}

document.addEventListener('DOMContentLoaded', initEditor);