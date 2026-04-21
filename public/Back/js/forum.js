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

async function reactToPostBack(postId, reaction) {
    const formData = new FormData();
    formData.append('reaction', reaction);

    const response = await fetch(`/forumBack/react/${postId}`, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const data = await response.json();
    if (!data.success) return;

    const label = document.getElementById(`reaction-label-${postId}`);
    const total = document.getElementById(`reaction-total-${postId}`);
    const btn = label ? label.closest('.btn-reaction-main') : null;

    if (label) label.textContent = formatReactionLabel(data.userReaction);
    if (total) total.textContent = data.totalCount;

    if (btn) {
        if (data.userReaction) btn.classList.add('reacted');
        else btn.classList.remove('reacted');
    }
}
/* ── View post modal ── */
function formatReactionLabel(reaction) {
    switch (reaction) {
        case 'LIKE': return '👍 Like';
        case 'LOVE': return '❤️ Love';
        case 'HAHA': return '😂 Haha';
        case 'WOW': return '😮 Wow';
        case 'SAD': return '😢 Sad';
        case 'ANGRY': return '😡 Angry';
        default: return 'React';
    }
}
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
<div class="comment-item ${c.hasBadWordMask ? 'comment-flagged' : ''}">
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

    ${c.hasBadWordMask ? `
        <div class="comment-flag-badge">🚩 Censored comment</div>
    ` : ''}

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

<div style="margin-top:16px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
    <button type="button"
            class="btn-read-more"
            onclick="translatePostBack(${id}, 'en')">
        Translate to English
    </button>

    <button type="button"
            class="btn-read-more"
            onclick="translatePostBack(${id}, 'fr')">
        Translate to French
    </button>

    <button type="button"
            class="btn-read-more"
            onclick="translatePostBack(${id}, 'ar')">
        Translate to Arabic
    </button>
</div>

    <div id="translated-box-${id}" style="display:none; background:#f3f4f6; padding:10px; border-radius:10px;">
        <div id="translated-label-${id}" style="font-weight:bold;"></div>
        <div id="translated-content-${id}"></div>
    </div>
</div>

       <div class="view-modal-footer">
    <div class="reaction-box">
        <button
            type="button"
            class="btn-reaction-main ${post.userReaction ? 'reacted' : ''}"
            onclick="toggleReactionMenu(event, ${id})"
        >
            <span id="reaction-label-${id}">
                ${formatReactionLabel(post.userReaction)}
            </span>
            <span id="reaction-total-${id}"
                  onclick="event.stopPropagation(); openReactionsModalBack(${id})"
                  style="cursor:pointer;">
                ${post.totalReactions || ''}
            </span>
        </button>

        <div class="reaction-menu" id="reaction-menu-${id}">
            <button type="button" onclick="reactToPostBack(${id}, 'LIKE')">👍</button>
            <button type="button" onclick="reactToPostBack(${id}, 'LOVE')">❤️</button>
            <button type="button" onclick="reactToPostBack(${id}, 'HAHA')">😂</button>
            <button type="button" onclick="reactToPostBack(${id}, 'WOW')">😮</button>
            <button type="button" onclick="reactToPostBack(${id}, 'SAD')">😢</button>
            <button type="button" onclick="reactToPostBack(${id}, 'ANGRY')">😡</button>
        </div>
    </div>

    ${isOwner ? `
    <button type="button" class="btn-update" onclick="openEditModal(${id})">
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


let editCommentId = null;

function editComment(postId, index) {
    const comment = POSTS_DATA[postId].comments[index];
    if (!comment) return;

    editCommentId = comment.id;
    document.getElementById('editCommentContent').value = comment.content;
    document.getElementById('editCommentForm').action = `/forumBack/comment/update/${comment.id}`;
    document.getElementById('editCommentModal').classList.add('open');
    document.body.style.overflow = 'hidden';

    // optional: close the small 3-dots menu
    const menu = document.getElementById(`menu-${postId}-${index}`);
    if (menu) menu.style.display = 'none';
}

/* Close on overlay click */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay && overlay.id) {
            closeModal(overlay.id);
        }
    });
});

/* Close on ESC */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => {
            if (m.id) closeModal(m.id);
        });
    }
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
const editCommentForm = document.getElementById('editCommentForm');
if (editCommentForm) {
    editCommentForm.addEventListener('submit', function (e) {
        const content = document.getElementById('editCommentContent').value.trim();

        this.querySelectorAll('.modal-inline-error').forEach(el => el.remove());

        if (!content) {
            e.preventDefault();
            showModalErrors(this, ['Comment cannot be empty.']);
            return;
        }

        if (content.length < 2) {
            e.preventDefault();
            showModalErrors(this, ['Comment must be at least 2 characters.']);
            return;
        }
    });
}

let deleteFormToSubmit = null;
let deleteCommentUrl = null;


function openDeleteModal(button) {
     console.log('delete clicked');
    deleteFormToSubmit = button.closest('form');
    document.getElementById('deleteConfirmModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function confirmDeletePost() {
    if (deleteFormToSubmit) {
        deleteFormToSubmit.submit();
    }
}

function deleteComment(postId, index) {
    const comment = POSTS_DATA[postId].comments[index];
    if (!comment) return;

    deleteCommentUrl = `/forumBack/comment/delete/${comment.id}`;
    document.getElementById('deleteCommentModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function confirmDeleteComment() {
    if (!deleteCommentUrl) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = deleteCommentUrl;
    document.body.appendChild(form);
    form.submit();
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('open');

    if (id === 'deleteConfirmModal') {
        deleteFormToSubmit = null;
    }

    if (id === 'deleteCommentModal') {
        deleteCommentUrl = null;
    }

    if (id === 'editCommentModal') {
        editCommentId = null;
        const form = document.getElementById('editCommentForm');
        if (form) form.querySelectorAll('.modal-inline-error').forEach(el => el.remove());
    }

    document.body.style.overflow = '';
}
function toggleReactionMenu(event, postId) {
    event.stopPropagation();

    const menu = document.getElementById(`reaction-menu-${postId}`);

    // close all menus first
    document.querySelectorAll('.reaction-menu').forEach(m => {
        if (m !== menu) m.classList.remove('open');
    });

    // toggle current
    menu.classList.toggle('open');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.reaction-box')) {
        document.querySelectorAll('.reaction-menu').forEach(menu => {
            menu.classList.remove('open');
        });
    }
});
async function reactToPostBack(postId, reaction) {
    const formData = new FormData();
    formData.append('reaction', reaction);

    const response = await fetch(`/forumBack/react/${postId}`, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const data = await response.json();
    if (!data.success) return;

    const label = document.getElementById(`reaction-label-${postId}`);
    const total = document.getElementById(`reaction-total-${postId}`);
    const btn = label ? label.closest('.btn-reaction-main') : null;

    if (label) label.textContent = formatReactionLabel(data.userReaction);
    if (total) total.textContent = data.totalCount;

    if (btn) {
        if (data.userReaction) btn.classList.add('reacted');
        else btn.classList.remove('reacted');
    }

    // ✅ CLOSE MENU AFTER CLICK
    document.getElementById(`reaction-menu-${postId}`).classList.remove('open');
}
function getReactionEmoji(reaction) {
    switch (reaction) {
        case 'LIKE': return '👍';
        case 'LOVE': return '❤️';
        case 'HAHA': return '😂';
        case 'WOW': return '😮';
        case 'SAD': return '😢';
        case 'ANGRY': return '😡';
        default: return '•';
    }
}

function renderReactionsListBack(reactions, filter = 'ALL') {
    const list = document.getElementById('reactionsList');
    if (!list) return;

    const filtered = filter === 'ALL'
        ? reactions
        : reactions.filter(item => item.reaction === filter);

    if (filtered.length === 0) {
        list.innerHTML = `<p style="color:#888;">No reactions found.</p>`;
        return;
    }

    list.innerHTML = filtered.map(item => `
        <div style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:10px 0;
            border-bottom:1px solid #eee;
            gap:12px;
        ">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="
                    width:38px;
                    height:38px;
                    border-radius:50%;
                    overflow:hidden;
                    background:#e5e7eb;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-weight:600;
                    color:#374151;
                    flex-shrink:0;
                ">
                    ${item.profileImage
                        ? `<img src="/${item.profileImage}" alt="${escapeHtml(item.author)}" style="width:100%;height:100%;object-fit:cover;">`
                        : escapeHtml(item.author).slice(0, 2).toUpperCase()
                    }
                </div>
                <div>${escapeHtml(item.author)}</div>
            </div>

            <div style="font-weight:600;">
                ${getReactionEmoji(item.reaction)}
            </div>
        </div>
    `).join('');
}

function renderReactionFiltersBack(reactions) {
    const container = document.getElementById('reactionsFilters');
    if (!container) return;

    const counts = {
        LIKE: 0,
        LOVE: 0,
        HAHA: 0,
        WOW: 0,
        SAD: 0,
        ANGRY: 0
    };

    reactions.forEach(item => {
        if (counts[item.reaction] !== undefined) {
            counts[item.reaction]++;
        }
    });

    const filters = [
        { key: 'ALL', label: `All ${reactions.length}` },
        { key: 'LIKE', label: `👍 ${counts.LIKE}` },
        { key: 'LOVE', label: `❤️ ${counts.LOVE}` },
        { key: 'HAHA', label: `😂 ${counts.HAHA}` },
        { key: 'WOW', label: `😮 ${counts.WOW}` },
        { key: 'SAD', label: `😢 ${counts.SAD}` },
        { key: 'ANGRY', label: `😡 ${counts.ANGRY}` }
    ];

    container.innerHTML = filters.map(filter => `
        <button type="button"
                data-filter="${filter.key}"
                style="
                    padding:6px 12px;
                    border:1px solid #d1d5db;
                    border-radius:999px;
                    background:#fff;
                    cursor:pointer;
                ">
            ${filter.label}
        </button>
    `).join('');

    container.querySelectorAll('button').forEach(button => {
        button.addEventListener('click', () => {
            renderReactionsListBack(reactions, button.dataset.filter);

            container.querySelectorAll('button').forEach(btn => {
                btn.style.background = '#fff';
                btn.style.color = '#111';
            });

            button.style.background = '#111';
            button.style.color = '#fff';
        });
    });

    const allBtn = container.querySelector('button[data-filter="ALL"]');
    if (allBtn) {
        allBtn.style.background = '#111';
        allBtn.style.color = '#fff';
    }
}

async function openReactionsModalBack(postId) {
    const list = document.getElementById('reactionsList');
    const filters = document.getElementById('reactionsFilters');

    if (list) list.innerHTML = `<p style="color:#888;">Loading...</p>`;
    if (filters) filters.innerHTML = '';

    document.getElementById('reactionsModal').classList.add('open');
    document.body.style.overflow = 'hidden';

    try {
        const response = await fetch(`/forumBack/reactions/${postId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        console.log('BACK REACTIONS RAW RESPONSE:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.error('Invalid JSON response:', raw);
            if (list) list.innerHTML = `<p style="color:red;">Server returned invalid JSON.</p>`;
            return;
        }

        if (!data.success) {
            if (list) list.innerHTML = `<p style="color:red;">${escapeHtml(data.message || 'Failed to load reactions.')}</p>`;
            return;
        }

        const reactions = data.reactions || [];
        renderReactionFiltersBack(reactions);
        renderReactionsListBack(reactions, 'ALL');
    } catch (error) {
        console.error(error);
        if (list) list.innerHTML = `<p style="color:red;">Error loading reactions.</p>`;
    }
}
async function translatePostBack(postId, language) {
    const box = document.getElementById(`translated-box-${postId}`);
    const label = document.getElementById(`translated-label-${postId}`);
    const content = document.getElementById(`translated-content-${postId}`);

    box.style.display = 'block';
    label.textContent = 'Translating...';
    content.textContent = '';

    try {
        const formData = new FormData();
        formData.append('language', language);

        const response = await fetch(`/forumBack/translate/${postId}`, {
            method: 'POST',
            body: formData
        });

       const raw = await response.text();
console.log('TRANSLATE BACK RAW RESPONSE:', raw);

let data;
try {
    data = JSON.parse(raw);
} catch (e) {
    label.textContent = 'Error';
    content.textContent = 'Server returned invalid JSON';
    return;
}

if (!data.success) {
    label.textContent = 'Error';
    content.textContent = data.message || 'Translation failed';
    console.error('Translation backend error:', data);
    return;
}

        const names = { en: 'English', fr: 'French', ar: 'Arabic' };

        label.textContent = 'Translated to ' + names[data.language];
        content.textContent = data.translatedText;

    } catch (e) {
        label.textContent = 'Error';
        content.textContent = 'Translation failed';
    }
}
