async function loadBlog() {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    if (!id) {
        document.getElementById('blogDetail').innerHTML = '<p style="text-align:center; padding:2rem;">No blog ID specified.</p>';
        return;
    }

    try {
        const blog = await fetchAPI(`blog.php?action=single&id=${id}`);
        const container = document.getElementById('blogDetail');
        const user = getCurrentUser();

        container.innerHTML = `
            <div class="blog-content" style="background:#fff; padding:2rem; border-radius:12px; box-shadow:0 5px 30px rgba(0,0,0,0.08);">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:0.5rem;">
                    <span style="background:#2b2b2b; color:#fff; padding:0.3rem 1.2rem; border-radius:20px; font-size:0.85rem;">
                        <i class="far fa-user"></i> ${escapeHtml(blog.author)}
                    </span>
                    <span style="color:#888; font-size:0.9rem;">
                        <i class="far fa-calendar-alt"></i> ${new Date(blog.created_at).toLocaleDateString()}
                    </span>
                </div>
                <h1 style="font-family:'Playfair Display',serif; font-size:2.5rem; margin:1rem 0 0.5rem 0;">${escapeHtml(blog.title)}</h1>
                <hr style="margin:1rem 0;">
                <div style="font-size:1.1rem; line-height:1.8; white-space:pre-wrap;">${blog.content}</div>
            </div>
        `;

        // Show Edit/Delete buttons only if current user is the author
        if (user && user.id === blog.user_id) {
            const actions = document.createElement('div');
            actions.style.cssText = 'display:flex; gap:1rem; margin-top:1.5rem; justify-content:center; flex-wrap:wrap;';
            actions.innerHTML = `
                <a href="editor.html?id=${blog.id}" style="background:#2b2b2b; color:#fff; padding:0.6rem 2rem; border-radius:30px; text-decoration:none; transition:0.3s;">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button id="deleteBtn" style="background:#e74c3c; color:#fff; padding:0.6rem 2rem; border:none; border-radius:30px; cursor:pointer; transition:0.3s;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            `;
            container.appendChild(actions);

            document.getElementById('deleteBtn').addEventListener('click', async () => {
                if (confirm('Are you sure you want to delete this blog permanently?')) {
                    try {
                        await fetchAPI(`blog.php?id=${blog.id}`, { method: 'DELETE' });
                        window.location.href = 'index.html';
                    } catch (err) {
                        alert('Delete failed: ' + err.message);
                    }
                }
            });
        }
    } catch (err) {
        document.getElementById('blogDetail').innerHTML = `
            <div style="text-align:center; color:#e74c3c; padding:2rem;">
                <i class="fas fa-exclamation-circle" style="font-size:2rem; display:block; margin-bottom:1rem;"></i>
                ${err.message}
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded', loadBlog);