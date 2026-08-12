async function loadBlog() {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (!id) {
        document.getElementById('blogDetail').innerHTML = '<p>No blog ID specified.</p>';
        return;
    }
    try {
        const blog = await fetchAPI(`blog.php?action=single&id=${id}`);
        const container = document.getElementById('blogDetail');
        container.innerHTML = `
            <div class="card">
                <h1>${escapeHtml(blog.title)}</h1>
                <div class="meta">By ${escapeHtml(blog.author)} on ${new Date(blog.created_at).toLocaleString()}</div>
                <div>${blog.content}</div> <!-- markdown content; we could render later -->
                <hr>
                <a href="index.html">Back to Home</a>
            </div>
        `;
        // Show edit/delete buttons if current user owns it
        const user = getCurrentUser();
        if (user && user.id === blog.user_id) {
            const actions = document.createElement('div');
            actions.innerHTML = `
                <a href="editor.html?id=${blog.id}" class="btn">Edit</a>
                <button id="deleteBtn" class="btn btn-danger">Delete</button>
            `;
            container.appendChild(actions);
            document.getElementById('deleteBtn').addEventListener('click', async () => {
                if (confirm('Delete this blog?')) {
                    await fetchAPI(`blog.php?id=${blog.id}`, { method: 'DELETE' });
                    window.location.href = 'index.html';
                }
            });
        }
    } catch (err) {
        document.getElementById('blogDetail').innerHTML = `<p>Error: ${err.message}</p>`;
    }
}

document.addEventListener('DOMContentLoaded', loadBlog);