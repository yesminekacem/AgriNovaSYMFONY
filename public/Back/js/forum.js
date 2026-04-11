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

/* ── View post modal ── */
function openViewModal(id) {
    const post = POSTS_DATA[id];
    if (!post) return;

    const isOwner = CURRENT_USER_ID !== null && Number(post.authorId) === Number(CURRENT_USER_ID);

    let commentsHtml = '';
    if (post.comments.length > 0) {
        let items = '';
        post.comments.forEach((c, index) => {
           const isCommentOwner = CURRENT_USER_ID !== null && Number(c.authorId) === Number(CURRENT_USER_ID);

items += `
<div class="comment-item">
    <div class="comment-header">
        <span class="comment-author">${escapeHtml(c.author)}</span>
        <div style="position:relative;">
            <button type="button" class="comment-menu-btn"
                    onclick="toggleCommentMenu(${id}, ${index})">⋯</button>
            <div class="comment-menu-dropdown" id="menu-${id}-${index}">
                ${isCommentOwner ? `<button type="button" onclick="editComment(${id}, ${index})">✏️ Edit</button>` : ''}
                <button type="button" class="btn-menu-delete" onclick="deleteComment(${id}, ${index})">🗑 Delete</button>
            </div>
        </div>
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
            <button type="button" class="btn-like" onclick="toggleLike(this, event)" data-count="0">
                <svg viewBox="0 0 24 24">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <span class="like-count">0</span>
            </button>

            ${isOwner ? `
            <button type="button" class="btn-update" onclick="openEditModal(${post.id})">
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
            <form method="post" action="/forumBack/comment/${post.id}" onsubmit="return validateCommentForm(this)">
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

function openNewPost() { document.getElementById('newPostModal').classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEditModal(id) {
    const post = POSTS_DATA[id];
    if (!post) return;
    document.getElementById('editTitle').value = post.title;
    document.getElementById('editCategory').value = post.category;
    document.getElementById('editContent').value = post.content;
    document.getElementById('editPostForm').action = `/forumBack/update/${id}`;
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
    form.action = `/forumBack/comment/delete/${comment.id}`;
    document.body.appendChild(form);
    form.submit();
}

function editComment(postId, index) {
    const comment = POSTS_DATA[postId].comments[index];
    const newContent = prompt("Edit your comment:", comment.content);
    if (!newContent) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/forumBack/comment/update/${comment.id}`;
    const input = document.createElement('input');
    input.name = 'content';
    input.value = newContent;
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
document.addEventListener('click', function (e) {
    if (!e.target.classList.contains('comment-menu-btn')) {
        document.querySelectorAll('.comment-menu-dropdown').forEach(m => m.style.display = 'none');
    }
});

/* ══════════════════════════════════════════════
   VALIDATION & FLASH MESSAGE HANDLING
══════════════════════════════════════════════ */

/* ── Helper: show errors inside a modal form ── */
function showModalErrors(form, errors) {
    // Remove any previous error box
    form.querySelectorAll('.modal-inline-error').forEach(el => el.remove());

    const box = document.createElement('div');
    box.className = 'modal-inline-error';
    box.style.cssText = `
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
        border-radius: 8px;
        padding: 10px 14px;
        margin-bottom: 14px;
        font-size: 0.85rem;
        line-height: 1.8;
    `;
    box.innerHTML = errors.map(err => `<div>⚠️ ${err}</div>`).join('');
    form.prepend(box);

    // Scroll modal to top so error is visible
    const modal = form.closest('.modal');
    if (modal) modal.scrollTop = 0;
}

/* ── Helper: show toast notification ── */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const bg = type === 'success' ? '#16a34a' : '#dc2626';
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: ${bg};
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 0.9rem;
        z-index: 99999;
        box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    `;
    toast.textContent = (type === 'success' ? '✅ ' : '❌ ') + message;
    document.body.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    // Animate out and remove
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

/* ── Validate post fields, returns array of error strings ── */
function validatePostFields(title, category, content) {
    const errors = [];

    if (!title) errors.push('Title is required.');
    else if (title.length < 3) errors.push('Title must be at least 3 characters.');
    else if (title.length > 200) errors.push('Title cannot exceed 200 characters.');

    if (!category) errors.push('Category is required.');

    if (!content) errors.push('Content is required.');
    else if (content.length < 10) errors.push('Content must be at least 10 characters.');

    return errors;
}

/* ── Validate comment form (used inline via onsubmit) ── */
function validateCommentForm(form) {
    const content = form.querySelector('[name="content"]').value.trim();
    form.querySelectorAll('.modal-inline-error').forEach(el => el.remove());

    if (!content) {
        showModalErrors(form, ['Comment cannot be empty.']);
        return false;
    }
    if (content.length < 2) {
        showModalErrors(form, ['Comment must be at least 2 characters.']);
        return false;
    }
    return true;
}

/* ── Wire up validation on DOMContentLoaded ── */
document.addEventListener('DOMContentLoaded', function () {

    // ── New Post form: client-side validation ──
    const newPostForm = document.querySelector('#newPostModal form');
    if (newPostForm) {
        newPostForm.addEventListener('submit', function (e) {
            const title    = this.querySelector('[name="title"]').value.trim();
            const category = this.querySelector('[name="category"]').value.trim();
            const content  = this.querySelector('[name="content"]').value.trim();

            const errors = validatePostFields(title, category, content);
            if (errors.length > 0) {
                e.preventDefault();
                showModalErrors(this, errors);
            }
        });
    }

    // ── Edit Post form: client-side validation ──
    const editPostForm = document.getElementById('editPostForm');
    if (editPostForm) {
        editPostForm.addEventListener('submit', function (e) {
            const title    = this.querySelector('[name="title"]').value.trim();
            const category = this.querySelector('[name="category"]').value.trim();
            const content  = this.querySelector('[name="content"]').value.trim();

            const errors = validatePostFields(title, category, content);
            if (errors.length > 0) {
                e.preventDefault();
                showModalErrors(this, errors);
            }
        });
    }

    // ── Server-side flash messages: hide page-level, show inside modal / toast ──
    const flashErrors   = document.querySelectorAll('.flash-error');
    const flashSuccesses = document.querySelectorAll('.flash-success');

    if (flashErrors.length > 0) {
        // Hide page-level error divs
        flashErrors.forEach(el => el.style.display = 'none');

        // Collect error texts
        const errors = [...flashErrors].map(el => el.textContent.trim()).filter(Boolean);

        // Re-open new post modal and show errors inside it
        openNewPost();
        const form = document.querySelector('#newPostModal form');
        if (form) showModalErrors(form, errors);
    }

    if (flashSuccesses.length > 0) {
        // Hide page-level success divs
        flashSuccesses.forEach(el => el.style.display = 'none');

        const msg = flashSuccesses[0].textContent.trim();
        showToast(msg, 'success');
    }
});