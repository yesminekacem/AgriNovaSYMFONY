/* ── Active category filter ── */
let activeCategory = 'All';

function setFilter(category, btn) {
    activeCategory = category;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

function applyFilters() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('#postsList .post-card');
    let visible = 0;

    cards.forEach(card => {
        const cardCat = (card.dataset.category || '').trim().toLowerCase();
        const activeCat = (activeCategory || 'All').trim().toLowerCase();

        const title = (card.querySelector('.post-title')?.textContent || '').toLowerCase();
        const excerpt = (card.querySelector('.post-excerpt')?.textContent || '').toLowerCase();
        const author = (card.querySelector('.post-meta')?.textContent || '').toLowerCase();

        const matchCat = (activeCat === 'all') || (cardCat === activeCat);
        const matchSearch = !query || title.includes(query) || excerpt.includes(query) || author.includes(query);

        if (matchCat && matchSearch) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('noResults').style.display =
        visible === 0 && cards.length > 0 ? 'block' : 'none';
}


/* ── Like toggle ── */
function toggleLike(btn, e) {
    e.stopPropagation();
    const countSpan = btn.querySelector('.like-count');
    let count = parseInt(btn.dataset.count, 10) || 0;

    if (btn.classList.contains('liked')) {
        btn.classList.remove('liked');
        count = Math.max(0, count - 1);
    } else {
        btn.classList.add('liked');
        count++;
        btn.querySelector('svg').animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(1.4)' }, { transform: 'scale(1)' }],
            { duration: 300, easing: 'ease-out' }
        );
    }
    btn.dataset.count = count;
    countSpan.textContent = count;
}

/* ── View post mfodal ── */
function openViewModal(id) {
    const post = POSTS_DATA[id];
    const isOwner = CURRENT_USER_ID !== null && Number(post.authorId) === Number(CURRENT_USER_ID);
    if (!post) return;

    let commentsHtml = '';
    if (post.comments.length > 0) {
        let items = '';
        post.comments.forEach((c, index) => {
          const isCommentOwner = CURRENT_USER_ID !== null && Number(c.authorId) === Number(CURRENT_USER_ID);

items += `
<div class="comment-item">
    <div class="comment-header">
        <span class="comment-author">${escapeHtml(c.author)}</span>
        ${isCommentOwner ? `
        <div style="position:relative;">
            <button class="comment-menu-btn"
                    onclick="toggleCommentMenu(${id}, ${index})">⋯</button>
            <div class="comment-menu-dropdown" id="menu-${id}-${index}">
                <button onclick="editComment(${id}, ${index})">✏️ Edit</button>
                <button class="btn-menu-delete" onclick="deleteComment(${id}, ${index})">🗑 Delete</button>
            </div>
        </div>
        ` : ''}
    </div>
                <div class="comment-time">${escapeHtml(c.createdAt)}</div>
                <div class="comment-content">${escapeHtml(c.content)}</div>
            </div>`;
        });

        commentsHtml = `
        <div class="comments-section">
            <div class="comments-title">💬 Comments (${post.comments.length})</div>
            ${items}
        </div>`;
    } else {
        commentsHtml = `
        <div class="comments-section">
            <div class="comments-title">💬 Comments</div>
            <p style="color:#aaa;font-size:0.9rem;">No comments yet. Be the first!</p>
        </div>`;
    }

    const tagClassMap = {
        'Organic Farming': 'tag-organic',
        'Soil Management': 'tag-soil',
        'Water Management': 'tag-water',
        'Harvesting': 'tag-harvest',
        'Crop Management': 'tag-crop'
    };

    const tagClass = tagClassMap[post.category] || 'tag-organic';

    document.getElementById('viewModalContent').innerHTML = `
        <span class="view-modal-tag ${tagClass}">${escapeHtml(post.category)}</span>
        <div class="view-modal-title">${escapeHtml(post.title)}</div>

        <div class="view-modal-meta">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>${escapeHtml(post.author)}</span>
            <span class="view-modal-sep">·</span>
            <span>${escapeHtml(post.createdAt)}</span>
        </div>

        ${post.image ? `<img src="/${post.image}" style="width:100%; max-height:250px; object-fit:cover; border-radius:12px; margin-bottom:1rem;">` : ''}

        <div class="view-modal-body">${escapeHtml(post.content)}</div>

        <div class="view-modal-footer">
            <button class="btn-like" onclick="toggleLike(this, event)" data-count="0">
                <svg viewBox="0 0 24 24">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <span class="like-count">0</span>
            </button>

          ${isOwner ? `
<button class="btn-update" onclick="openEditModal(${post.id})">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 20h9"/>
        <path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
    </svg>
    Update
</button>
` : ''}
        </div>

        ${commentsHtml}

        <div class="add-comment-section">
            <label class="add-comment-label">Add a comment</label>
            <form method="post" action="/forum/comment/${post.id}">
                <textarea name="content" placeholder="Write your comment..." required></textarea>
                <button type="submit" class="btn-post-comment">Post Comment</button>
            </form>
        </div>
    `;

    document.getElementById('viewModal').classList.add('open');
}

  
/* ── Helpers ── */
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function openNewPost()       { document.getElementById('newPostModal').classList.add('open'); }
function closeModal(id)      { document.getElementById(id).classList.remove('open'); }

function openEditModal(id) {
    const post = POSTS_DATA[id];
    if (!post) return;
    document.getElementById('editTitle').value    = post.title;
    document.getElementById('editCategory').value = post.category;
    document.getElementById('editContent').value  = post.content;
    document.getElementById('editPostForm').action = `/forum/update/${id}`;
    closeModal('viewModal');
    document.getElementById('editPostModal').classList.add('open');
}

function toggleCommentMenu(postId, index) {
    const menu = document.getElementById(`menu-${postId}-${index}`);
    if (!menu) return;
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function deleteComment(postId, index) {
    const comment = POSTS_DATA[postId].comments[index];
    if (!confirm("Delete this comment?")) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/forum/comment/delete/${comment.id}`;
    document.body.appendChild(form);
    form.submit();
}

function editComment(postId, index) {
    const comment = POSTS_DATA[postId].comments[index];
    const newContent = prompt("Edit your comment:", comment.content);
    if (!newContent) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/forum/comment/update/${comment.id}`;
    const input = document.createElement('input');
    input.name = 'content'; input.value = newContent;
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

/* Close on overlay click */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});

/* Close on ESC */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
        document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

/* Close comment dropdowns on outside click */
document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('comment-menu-btn')) {
        document.querySelectorAll('.comment-menu-dropdown').forEach(m => m.style.display = 'none');
    }
});
