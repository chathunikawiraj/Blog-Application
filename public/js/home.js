let currentPage = 1;

async function loadBlogs(page = 1) {
    try {
        const data = await fetchAPI(`blog.php?action=list&page=${page}`);
        const blogList = document.getElementById('blogList');
        blogList.innerHTML = '';
        if (data.blogs.length === 0) {
            blogList.innerHTML = '<p>No blogs yet.</p>';
        } else {
            data.blogs.forEach(blog => {
                const div = document.createElement('div');
                div.className = 'card';
                div.innerHTML = `
                    <h2><a href="blog.html?id=${blog.id}">${escapeHtml(blog.title)}</a></h2>
                    <div class="meta">By ${escapeHtml(blog.author)} on ${new Date(blog.created_at).toLocaleDateString()}</div>
                    <p>${escapeHtml(blog.content.substring(0, 150))}...</p>
                    <a href="blog.html?id=${blog.id}" class="btn">Read More</a>
                `;
                blogList.appendChild(div);
            });
        }
        // Pagination
        const totalPages = Math.ceil(data.total / 10);
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = (i === page) ? 'btn active' : 'btn';
            btn.addEventListener('click', () => { loadBlogs(i); });
            pagination.appendChild(btn);
        }
        currentPage = page;
    } catch (err) {
        alert('Failed to load blogs: ' + err.message);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', loadBlogs);