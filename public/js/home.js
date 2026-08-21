console.log("✅ home.js loaded (v2)!");

async function loadBlogs(page = 1) {
    console.log("📡 Fetching page:", page);
    try {
        const data = await fetchAPI(`blog.php?action=list&page=${page}`);
        console.log("📦 Data received:", data);
        const blogList = document.getElementById('blogList');
        blogList.innerHTML = '';

        if (!data.blogs || data.blogs.length === 0) {
            blogList.innerHTML = `
                <div style="grid-column:1/-1; text-align:center; padding:3rem 0; color:#888;">
                    <i class="fas fa-pen-fancy" style="font-size:3rem; display:block; margin-bottom:1rem; color:var(--gold);"></i>
                    <p style="font-size:1.2rem;">No blogs yet. Be the first to share your thoughts!</p>
                </div>
            `;
            return;
        }

        data.blogs.forEach(function(blog) {
            const div = document.createElement('div');
            div.className = 'blog-item';

            // ✅ Use thumbnail from API
            const thumbnail = blog.thumbnail || 'https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=600';
            
            // ✅ Use excerpt from API (clean, no Markdown)
            const excerpt = blog.excerpt || blog.content.substring(0, 150) + '...';

            div.innerHTML = `
                <div class="blog-img">
                    <img src="${thumbnail}" alt="${escapeHtml(blog.title)}">
                    <span><i class="far fa-calendar-alt"></i> ${new Date(blog.created_at).toLocaleDateString()}</span>
                </div>
                <div class="blog-text">
                    <span>By ${escapeHtml(blog.author)}</span>
                    <h2>${escapeHtml(blog.title)}</h2>
                    <p>${escapeHtml(excerpt)}</p>
                    <a href="blog.html?id=${blog.id}">Read More <i class="fas fa-arrow-right"></i></a>
                </div>
            `;
            blogList.appendChild(div);
        });

        // Pagination
        const totalPages = Math.ceil(data.total / 10);
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';
        if (totalPages > 1) {
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.style.cssText = `
                    background: ${i === page ? '#c9a96e' : '#eee'};
                    color: ${i === page ? '#fff' : '#333'};
                    border: none;
                    padding: 0.5rem 1rem;
                    border-radius: 30px;
                    cursor: pointer;
                    transition: 0.3s;
                    font-weight: ${i === page ? '600' : '400'};
                `;
                btn.onclick = (function(p) {
                    return function() { loadBlogs(p); };
                })(i);
                pagination.appendChild(btn);
            }
        }
    } catch (err) {
        console.error("❌ Error loading blogs:", err);
        document.getElementById('blogList').innerHTML = `
            <div style="grid-column:1/-1; text-align:center; color:#e74c3c; padding:2rem;">
                <i class="fas fa-exclamation-circle" style="font-size:2rem; display:block; margin-bottom:1rem;"></i>
                Error: ${err.message}
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadBlogs(1);
});