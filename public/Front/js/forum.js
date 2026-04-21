/* ── Active category filter ── */
let activeCategory = 'All';
function getPostId(post) {
    return post?.id ?? post?.idPost ?? null;
}
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
function toggleReactionMenu(event, postId) {
    event.stopPropagation();

    document.querySelectorAll('.reaction-menu').forEach(menu => {
        if (menu.id !== `reaction-menu-${postId}`) {
            menu.style.display = 'none';
        }
    });

    const menu = document.getElementById(`reaction-menu-${postId}`);
    if (!menu) return;

    menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
}

async function reactToPost(postId, reaction) {
    try {
        const formData = new FormData();
        formData.append('reaction', reaction);

        const response = await fetch(`/forum/react/${postId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

    const raw = await response.text();
console.log('RAW RESPONSE:', raw);

let data;
try {
    data = JSON.parse(raw);
} catch (e) {
    console.error('Invalid JSON response:', raw);
    alert('Server returned HTML instead of JSON. Check Symfony error.');
    return;
}

if (!data.success) {
    alert(data.message || 'Reaction failed');
    return;
}

        const label = document.getElementById(`reaction-label-${postId}`);
        const total = document.getElementById(`reaction-total-${postId}`);
        const mainBtn = label ? label.closest('.btn-reaction-main') : null;
        const menu = document.getElementById(`reaction-menu-${postId}`);

        if (label) {
    label.textContent = formatReactionLabel(data.userReaction);
}

        if (total) {
            total.textContent = data.totalCount;
        }

        if (mainBtn) {
            if (data.userReaction) {
                mainBtn.classList.add('reacted');
            } else {
                mainBtn.classList.remove('reacted');
            }
        }

        if (menu) {
            menu.style.display = 'none';
        }

        if (POSTS_DATA[postId]) {
            POSTS_DATA[postId].userReaction = data.userReaction;
            POSTS_DATA[postId].totalReactions = data.totalCount;
            POSTS_DATA[postId].reactionCounts = data.counts;
        }
    } catch (error) {
    console.error(error);
    alert('Error sending reaction');
}
}

/* ── View post mfodal ── */
function openViewModal(id) {
    const post = POSTS_DATA[id];
     if (!post) return;
     const postId = getPostId(post);
    const isOwner = CURRENT_USER_ID !== null && Number(post.authorId) === Number(CURRENT_USER_ID);
    

    let commentsHtml = '';
    if (post.comments.length > 0) {
        let items = '';
        post.comments.forEach((c, index) => {
          const isCommentOwner = CURRENT_USER_ID !== null && Number(c.authorId) === Number(CURRENT_USER_ID);

items += `
<div class="comment-item">
    <div class="comment-header">
    <div class="comment-author-wrap">
        <div class="comment-avatar">
            ${c.profileImage
                ? `<img src="/${c.profileImage}" alt="${escapeHtml(c.author)}">`
                : escapeHtml(c.author).slice(0, 2).toUpperCase()
            }
        </div>
        <span class="comment-author">${escapeHtml(c.author)}</span>
    </div>
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

<div class="translate-section" style="margin-top:16px;">
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
        <button type="button" class="btn-read-more" onclick="translatePost(${postId}, 'en')">Translate to English</button>
        <button type="button" class="btn-read-more" onclick="translatePost(${postId}, 'fr')">Translate to French</button>
        <button type="button" class="btn-read-more" onclick="translatePost(${postId}, 'ar')">Translate to Arabic</button>
    </div>

    <div id="translated-post-box-${postId}" style="display:none; background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:12px;">
        <div id="translated-post-label-${postId}" style="font-weight:600; margin-bottom:8px;"></div>
        <div id="translated-post-content-${postId}" style="line-height:1.6;"></div>
    </div>
</div>

        <div class="view-modal-footer">
           <div class="reaction-box">
    <button
        type="button"
        class="btn-reaction-main ${post.userReaction ? 'reacted' : ''}"
        onclick="toggleReactionMenu(event, ${postId})"
    >
       <span id="reaction-label-${postId}">
    ${formatReactionLabel(post.userReaction)}
</span>
       <span id="reaction-total-${postId}"
      onclick="openReactionsModal(${postId})"
      style="cursor:pointer; text-decoration:underline;">
    ${post.totalReactions}
</span>
    </button>

    <div class="reaction-menu" id="reaction-menu-${postId}">
        <button type="button" onclick="reactToPost(${postId}, 'LIKE')">👍</button>
        <button type="button" onclick="reactToPost(${postId}, 'LOVE')">❤️</button>
        <button type="button" onclick="reactToPost(${postId}, 'HAHA')">😂</button>
        <button type="button" onclick="reactToPost(${postId}, 'WOW')">😮</button>
        <button type="button" onclick="reactToPost(${postId}, 'SAD')">😢</button>
        <button type="button" onclick="reactToPost(${postId}, 'ANGRY')">😡</button>
    </div>
</div>

          ${isOwner ? `
<button class="btn-update" onclick="openEditModal(${postId})">
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
    <form method="post" action="/forum/comment/${postId}" onsubmit="return submitComment(event, ${post.id}, this)">
        <div class="voice-comment-box">
            <textarea
                name="content"
                id="comment-textarea-${postId}"
                placeholder="Write your comment..."
                required
            ></textarea>

            <div class="voice-comment-actions">
                <button
                    type="button"
                    class="btn-voice-comment"
                    onclick="startVoiceComment(${postId})"
                >
                    🎤 Speak
                </button>

                <span class="voice-status" id="voice-status-${postId}">
                    Ready
                </span>
            </div>
        </div>

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
function openEditModal(id) {
    const post = POSTS_DATA[id];
    if (!post) return;

    const postId = getPostId(post) ?? id;

    document.getElementById('editTitle').value = post.title;
    document.getElementById('editCategory').value = post.category;
    document.getElementById('editContent').value = post.content;
    document.getElementById('editPostForm').action = `/forum/update/${postId}`;

    closeModal('viewModal');
    document.getElementById('editPostModal').classList.add('open');
}
function toggleCommentMenu(postId, index) {
    const menu = document.getElementById(`menu-${postId}-${index}`);
    if (!menu) return;
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

let deleteCommentUrl = null;

function deleteComment(postId, index) {
    const comment = POSTS_DATA[postId].comments[index];
    if (!comment) return;

    deleteCommentUrl = `/forum/comment/delete/${comment.id}`;
    document.getElementById('deleteCommentModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
async function confirmDeletePost() {
    if (!deleteFormToSubmit) return;

    try {
        const response = await fetch(deleteFormToSubmit.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        console.log('DELETE POST RAW RESPONSE:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.error('Invalid JSON response:', raw);
            alert('Server returned HTML instead of JSON for post delete.');
            return;
        }

        if (!data.success) {
            alert(data.message || 'Delete failed');
            return;
        }

        closeModal('deleteConfirmModal');
    } catch (error) {
        console.error(error);
        alert('Error deleting post');
    }
}

let editCommentId = null;

function editComment(postId, index) {
    const comment = POSTS_DATA[postId].comments[index];
    if (!comment) return;

    editCommentId = comment.id;
    document.getElementById('editCommentContent').value = comment.content;
    document.getElementById('editCommentForm').action = `/forum/comment/update/${comment.id}`;
    document.getElementById('editCommentModal').classList.add('open');
    document.body.style.overflow = 'hidden';

    const menu = document.getElementById(`menu-${postId}-${index}`);
    if (menu) menu.style.display = 'none';
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
/* ✅ CLOSE REACTION MENU WHEN CLICK OUTSIDE */
document.addEventListener('click', function(e) {
    if (!e.target.closest('.reaction-box')) {
        document.querySelectorAll('.reaction-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});
let deleteFormToSubmit = null;

function openDeleteModal(button) {
    deleteFormToSubmit = button.closest('form');
    document.getElementById('deleteConfirmModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

/* ── Helper: show errors inside modal form ── */
function showModalErrors(form, errors) {
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

    const modal = form.closest('.modal');
    if (modal) modal.scrollTop = 0;
}

/* ── Validate post fields ── */
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

/* ── Validate comment form ── */
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

/* ── Wire validation ── */
document.addEventListener('DOMContentLoaded', function () {
   const newPostForm = document.querySelector('#newPostModal form');
if (newPostForm) {
    newPostForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const title = this.querySelector('[name="title"]').value.trim();
        const category = this.querySelector('[name="category"]').value.trim();
        const content = this.querySelector('[name="content"]').value.trim();

        const errors = validatePostFields(title, category, content);

        if (errors.length > 0) {
            showModalErrors(this, errors);
            return;
        }

        try {
            const formData = new FormData(this);

            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const raw = await response.text();
            console.log('NEW POST RAW RESPONSE:', raw);

            let data;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                console.error('Invalid JSON response:', raw);
                alert('Server returned HTML instead of JSON for new post.');
                return;
            }

            if (!data.success) {
                showModalErrors(this, [data.message || 'Post creation failed']);
                return;
            }

            this.reset();
            closeModal('newPostModal');
        } catch (error) {
            console.error(error);
            alert('Error creating post');
        }
    });
}
    const editCommentForm = document.getElementById('editCommentForm');
if (editCommentForm) {
    editCommentForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const content = document.getElementById('editCommentContent').value.trim();

        this.querySelectorAll('.modal-inline-error').forEach(el => el.remove());

        if (!content) {
            showModalErrors(this, ['Comment cannot be empty.']);
            return;
        }

        if (content.length < 2) {
            showModalErrors(this, ['Comment must be at least 2 characters.']);
            return;
        }

        try {
            const formData = new FormData(this);

            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const raw = await response.text();
            console.log('EDIT COMMENT RAW RESPONSE:', raw);

            let data;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                console.error('Invalid JSON response:', raw);
                alert('Server returned HTML instead of JSON for comment edit.');
                return;
            }

            if (!data.success) {
                showModalErrors(this, [data.message || 'Update failed']);
                return;
            }

            closeModal('editCommentModal');
        } catch (error) {
            console.error(error);
            alert('Error updating comment');
        }
    });
}
 
        const flashErrors = document.querySelectorAll('.flash-error');
    const flashSuccesses = document.querySelectorAll('.flash-success');

    if (flashErrors.length > 0) {
        flashErrors.forEach(el => el.style.display = 'none');

        const errors = [...flashErrors].map(el => el.textContent.trim()).filter(Boolean);

        openNewPost();
        const form = document.querySelector('#newPostModal form');
        if (form) showModalErrors(form, errors);
    }

    if (flashSuccesses.length > 0) {
        flashSuccesses.forEach(el => el.style.display = 'none');
    }
const editPostForm = document.getElementById('editPostForm');
if (editPostForm) {
    editPostForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const title = this.querySelector('[name="title"]').value.trim();
        const category = this.querySelector('[name="category"]').value.trim();
        const content = this.querySelector('[name="content"]').value.trim();

        const errors = validatePostFields(title, category, content);

        if (errors.length > 0) {
            showModalErrors(this, errors);
            return;
        }

        try {
            const formData = new FormData(this);

            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const raw = await response.text();
            console.log('EDIT POST RAW RESPONSE:', raw);

            let data;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                console.error('Invalid JSON response:', raw);
                alert('Server returned HTML instead of JSON for post update.');
                return;
            }

           if (!data.success) {
    showModalErrors(this, [data.message || 'Post update failed']);
    return;
}

if (data.post) {
    const updatedPostId = data.post.id ?? data.post.idPost;

    if (updatedPostId && POSTS_DATA[updatedPostId]) {
        POSTS_DATA[updatedPostId] = {
            ...POSTS_DATA[updatedPostId],
            ...data.post
        };

        refreshPostCard(POSTS_DATA[updatedPostId]);

        const modal = document.getElementById('viewModal');
        if (modal && modal.classList.contains('open')) {
            openViewModal(updatedPostId);
        }
    }
}

closeModal('editPostModal');
        } catch (error) {
            console.error(error);
            alert('Error updating post');
        }
    });
}
});
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
    let activeRecognition = null;
let activeVoicePostId = null;

function startVoiceComment(postId) {
    const textarea = document.getElementById(`comment-textarea-${postId}`);
    const status = document.getElementById(`voice-status-${postId}`);

    if (!textarea || !status) return;

    const SpeechRecognition =
        window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        status.textContent = 'Speech recognition is not supported in this browser.';
        return;
    }

    if (activeRecognition) {
        activeRecognition.stop();
        activeRecognition = null;
        activeVoicePostId = null;
    }

    const recognition = new SpeechRecognition();
    activeRecognition = recognition;
    activeVoicePostId = postId;

    recognition.lang = 'en-US';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    status.textContent = 'Listening...';

    recognition.onresult = function (event) {
        const transcript = event.results[0][0].transcript.trim();

        if (transcript) {
            textarea.value = textarea.value
                ? textarea.value.trim() + ' ' + transcript
                : transcript;

            status.textContent = 'Voice inserted';
            textarea.focus();
        } else {
            status.textContent = 'No speech detected';
        }
    };

    recognition.onerror = function () {
        status.textContent = 'Voice recognition error';
    };

    recognition.onend = function () {
        if (activeVoicePostId === postId && status.textContent === 'Listening...') {
            status.textContent = 'Ready';
        }
        activeRecognition = null;
        activeVoicePostId = null;
    };

    recognition.start();
}
function subscribeGlobalPostsRealtime() {
    const url = new URL('http://127.0.0.1:3000/.well-known/mercure');
    url.searchParams.append('topic', 'http://127.0.0.1/forum/posts');

    const eventSource = new EventSource(url);

    eventSource.onmessage = (event) => {
        const data = JSON.parse(event.data);

        if (data.type === 'post_created') {
            window.location.reload();
            return;
        }

        if (data.type === 'post_updated') {
            if (!data.post) return;

            const updatedPostId = data.post.id ?? data.post.idPost;
            if (!updatedPostId) return;

            const card = document.querySelector(`.post-card[data-id="${updatedPostId}"]`);
            if (!card) return;

            POSTS_DATA[updatedPostId] = {
                ...POSTS_DATA[updatedPostId],
                ...data.post
            };

            refreshPostCard(POSTS_DATA[updatedPostId]);

            const modal = document.getElementById('viewModal');
            if (modal && modal.classList.contains('open')) {
                openViewModal(updatedPostId);
            }
        }

        if (data.type === 'post_deleted') {
            window.location.reload();
            return;
        }
    };
}
function subscribeRealtime() {
    Object.keys(POSTS_DATA).forEach((postId) => {
        const url = new URL('http://127.0.0.1:3000/.well-known/mercure');
url.searchParams.append('topic', `http://127.0.0.1/forum/posts/${postId}`);
        const eventSource = new EventSource(url);

        eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);

            if (data.type === 'reaction_updated') {
                if (!POSTS_DATA[data.postId]) return;

                POSTS_DATA[data.postId].totalReactions = data.totalCount;
                POSTS_DATA[data.postId].reactionCounts = data.counts;

                // update UI
                const total = document.getElementById(`reaction-total-${data.postId}`);
                if (total) total.textContent = data.totalCount;
            }
            if (data.type === 'comment_created') {
    if (!POSTS_DATA[data.postId]) return;

    POSTS_DATA[data.postId].comments.push(data.comment);
    POSTS_DATA[data.postId].commentsCount = data.commentsCount;

    const card = document.querySelector(`.post-card[data-id="${data.postId}"]`);
    const countBadge = card ? card.querySelector('.comments-count') : null;

    if (countBadge) {
        countBadge.textContent = `💬 ${data.commentsCount}`;
    }

    const modal = document.getElementById('viewModal');
    if (modal && modal.classList.contains('open')) {
        const currentPost = POSTS_DATA[data.postId];
        const titleEl = document.querySelector('#viewModalContent .view-modal-title');

        if (titleEl && currentPost && titleEl.textContent.trim() === currentPost.title) {
            openViewModal(data.postId);
        }
    }
}
if (data.type === 'post_updated') {
    if (!data.post) return;

    const updatedPostId = data.post.id ?? data.post.idPost;
    if (!updatedPostId || !POSTS_DATA[updatedPostId]) return;

    POSTS_DATA[updatedPostId] = {
        ...POSTS_DATA[updatedPostId],
        ...data.post
    };

    refreshPostCard(POSTS_DATA[updatedPostId]);

    const modal = document.getElementById('viewModal');
    if (modal && modal.classList.contains('open')) {
        openViewModal(updatedPostId);
    }
}
if (data.type === 'comment_deleted') {
    if (!POSTS_DATA[data.postId]) return;

    POSTS_DATA[data.postId].comments = POSTS_DATA[data.postId].comments.filter(
        c => c.id !== data.commentId
    );

    POSTS_DATA[data.postId].commentsCount = data.commentsCount;

    const card = document.querySelector(`.post-card[data-id="${data.postId}"]`);
    const countBadge = card ? card.querySelector('.comments-count') : null;

    if (countBadge) {
        countBadge.textContent = `💬 ${data.commentsCount}`;
    }

    const modal = document.getElementById('viewModal');
    if (modal && modal.classList.contains('open')) {
        const currentPost = POSTS_DATA[data.postId];
        const titleEl = document.querySelector('#viewModalContent .view-modal-title');

        if (titleEl && currentPost && titleEl.textContent.trim() === currentPost.title) {
            openViewModal(data.postId);
        }
    }
}
if (data.type === 'comment_updated') {
    if (!POSTS_DATA[data.postId]) return;

    POSTS_DATA[data.postId].comments = POSTS_DATA[data.postId].comments.map(c =>
        c.id === data.comment.id
            ? { ...c, content: data.comment.content, createdAt: data.comment.createdAt }
            : c
    );

    const modal = document.getElementById('viewModal');
    if (modal && modal.classList.contains('open')) {
        const currentPost = POSTS_DATA[data.postId];
        const titleEl = document.querySelector('#viewModalContent .view-modal-title');

        if (titleEl && currentPost && titleEl.textContent.trim() === currentPost.title) {
            openViewModal(data.postId);
        }
    }
}

        };
    });
}

document.addEventListener('DOMContentLoaded', () => {
    subscribeGlobalPostsRealtime();
    subscribeRealtime();
});
async function submitComment(event, postId, form) {
    event.preventDefault();

    if (!validateCommentForm(form)) {
        return false;
    }

    try {
        const formData = new FormData(form);

        const response = await fetch(`/forum/comment/${postId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        console.log('COMMENT RAW RESPONSE:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.error('Invalid JSON response:', raw);
            alert('Server returned HTML instead of JSON for comment.');
            return false;
        }

        if (!data.success) {
            alert(data.message || 'Comment failed');
            return false;
        }

        const textarea = form.querySelector('[name="content"]');
        if (textarea) {
            textarea.value = '';
        }

        return false;
    } catch (error) {
        console.error(error);
        alert('Error sending comment');
        return false;
    }
}
function deleteComment(postId, index) {
    const comment = POSTS_DATA[postId].comments[index];
    if (!comment) return;

    deleteCommentUrl = `/forum/comment/delete/${comment.id}`;
    document.getElementById('deleteCommentModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function insertPostCard(post) {
    const postsList = document.getElementById('postsList');
    if (!postsList) return;

    const card = document.createElement('div');
    card.className = 'post-card';
    card.dataset.category = (post.category || 'Organic Farming').trim();
    card.dataset.id = post.id;

    const avatarHtml = post.profileImage
        ? `<img src="/${post.profileImage}" alt="${escapeHtml(post.author)}">`
        : escapeHtml(post.author).slice(0, 2).toUpperCase();

    const imageHtml = post.image
        ? `<img src="/${post.image}" style="width:100%; max-height:160px; object-fit:cover; border-radius:10px; margin-bottom:10px;">`
        : '';

    const reactionLabel = formatReactionLabel(post.userReaction);

    const isOwner = CURRENT_USER_ID !== null && Number(post.authorId) === Number(CURRENT_USER_ID);

    const tagClassMap = {
        'Organic Farming': 'tag-organic',
        'Soil Management': 'tag-soil',
        'Water Management': 'tag-water',
        'Harvesting': 'tag-harvest',
        'Crop Management': 'tag-crop'
    };

    const tagClass = tagClassMap[post.category] || 'tag-organic';

    card.innerHTML = `
        <div class="post-avatar" onclick="openViewModal(${post.id})">
            ${avatarHtml}
        </div>
        <div class="post-body">
            <div class="post-top">
                <div class="post-title" onclick="openViewModal(${post.id})">${escapeHtml(post.title)}</div>
                <span class="post-tag ${tagClass}">${escapeHtml(post.category || 'Organic Farming')}</span>
            </div>

            <div class="post-meta">
                ${escapeHtml(post.author)} · ${escapeHtml(post.createdAt)}
            </div>

            ${imageHtml}

            <div class="post-excerpt" onclick="openViewModal(${post.id})">
                ${escapeHtml(post.content.length > 180 ? post.content.slice(0, 180) + '...' : post.content)}
            </div>

            <div class="post-footer">
                <div class="reaction-box">
                    <button
                        type="button"
                        class="btn-reaction-main ${post.userReaction ? 'reacted' : ''}"
                        onclick="toggleReactionMenu(event, ${post.id})"
                    >
                        <span id="reaction-label-${post.id}">${reactionLabel}</span>
                        <span id="reaction-total-${post.id}">${post.totalReactions || 0}</span>
                    </button>

                    <div class="reaction-menu" id="reaction-menu-${post.id}">
                        <button type="button" onclick="reactToPost(${post.id}, 'LIKE')">👍</button>
                        <button type="button" onclick="reactToPost(${post.id}, 'LOVE')">❤️</button>
                        <button type="button" onclick="reactToPost(${post.id}, 'HAHA')">😂</button>
                        <button type="button" onclick="reactToPost(${post.id}, 'WOW')">😮</button>
                        <button type="button" onclick="reactToPost(${post.id}, 'SAD')">😢</button>
                        <button type="button" onclick="reactToPost(${post.id}, 'ANGRY')">😡</button>
                    </div>
                </div>

                <span class="comments-count">💬 ${post.commentsCount || 0}</span>

                <button class="btn-read-more" onclick="openViewModal(${post.id})">
                    Read
                </button>

                ${isOwner ? `
                    <form method="post" action="/forum/delete/${post.id}" class="delete-post-form" style="margin-left:auto;">
                        <button type="button" class="btn-delete" onclick="openDeleteModal(this)">
                            Delete
                        </button>
                    </form>
                ` : ''}
            </div>
        </div>
    `;

    postsList.prepend(card);
}
function refreshPostCard(post) {
    const postId = getPostId(post);
const oldCard = document.querySelector(`.post-card[data-id="${postId}"]`);
if (!oldCard) return;

    oldCard.dataset.category = (post.category || 'Organic Farming').trim();

    const title = oldCard.querySelector('.post-title');
    if (title) title.textContent = post.title;

    const excerpt = oldCard.querySelector('.post-excerpt');
    if (excerpt) {
        excerpt.textContent = post.content.length > 180 ? post.content.slice(0, 180) + '...' : post.content;
    }

    const tag = oldCard.querySelector('.post-tag');
    if (tag) {
        tag.textContent = post.category || 'Organic Farming';

        const tagClassMap = {
            'Organic Farming': 'tag-organic',
            'Soil Management': 'tag-soil',
            'Water Management': 'tag-water',
            'Harvesting': 'tag-harvest',
            'Crop Management': 'tag-crop'
        };

        tag.className = `post-tag ${tagClassMap[post.category] || 'tag-organic'}`;
    }

    const reactionTotal = oldCard.querySelector(`#reaction-total-${postId}`)
    if (reactionTotal) reactionTotal.textContent = post.totalReactions || 0;

    const reactionLabel = oldCard.querySelector(`#reaction-label-${postId}`)
    if (reactionLabel) reactionLabel.textContent = formatReactionLabel(post.userReaction);

    const commentsCount = oldCard.querySelector('.comments-count');
    if (commentsCount) commentsCount.textContent = `💬 ${post.commentsCount || 0}`;

    const postImage = oldCard.querySelector('img[style*="max-height:160px"]');
    if (post.image && !postImage) {
        const excerptEl = oldCard.querySelector('.post-excerpt');
        if (excerptEl) {
            excerptEl.insertAdjacentHTML(
                'beforebegin',
                `<img src="/${post.image}" style="width:100%; max-height:160px; object-fit:cover; border-radius:10px; margin-bottom:10px;">`
            );
        }
    } else if (!post.image && postImage) {
        postImage.remove();
    } else if (post.image && postImage) {
        postImage.src = `/${post.image}`;
    }
}
async function confirmDeleteComment() {
    if (!deleteCommentUrl) return;

    try {
        const response = await fetch(deleteCommentUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        console.log('DELETE COMMENT RAW RESPONSE:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.error('Invalid JSON response:', raw);
            alert('Server returned HTML instead of JSON for comment delete.');
            return;
        }

        if (!data.success) {
            alert(data.message || 'Delete comment failed');
            return;
        }

        closeModal('deleteCommentModal');
    } catch (error) {
        console.error(error);
        alert('Error deleting comment');
    }
}
const POST_SUBSCRIPTIONS = {};

function subscribeToPost(postId) {
    postId = String(postId);

    if (POST_SUBSCRIPTIONS[postId]) {
        return;
    }

    const url = new URL('http://127.0.0.1:3000/.well-known/mercure');
    url.searchParams.append('topic', `http://127.0.0.1/forum/posts/${postId}`);

    const eventSource = new EventSource(url);
    POST_SUBSCRIPTIONS[postId] = eventSource;

    eventSource.onmessage = (event) => {
        const data = JSON.parse(event.data);
        console.log('POST TOPIC EVENT:', postId, data);

        if (data.type === 'reaction_updated') {
            if (!POSTS_DATA[data.postId]) return;

            POSTS_DATA[data.postId].totalReactions = data.totalCount;
            POSTS_DATA[data.postId].reactionCounts = data.counts;

            const total = document.getElementById(`reaction-total-${data.postId}`);
            if (total) total.textContent = data.totalCount;
        }

        if (data.type === 'comment_created') {
            if (!POSTS_DATA[data.postId]) return;

            POSTS_DATA[data.postId].comments.push(data.comment);
            POSTS_DATA[data.postId].commentsCount = data.commentsCount;

            const card = document.querySelector(`.post-card[data-id="${data.postId}"]`);
            const countBadge = card ? card.querySelector('.comments-count') : null;

            if (countBadge) {
                countBadge.textContent = `💬 ${data.commentsCount}`;
            }

            const modal = document.getElementById('viewModal');
            if (modal && modal.classList.contains('open')) {
                const currentPost = POSTS_DATA[data.postId];
                const titleEl = document.querySelector('#viewModalContent .view-modal-title');

                if (titleEl && currentPost && titleEl.textContent.trim() === currentPost.title) {
                    openViewModal(data.postId);
                }
            }
        }

        if (data.type === 'post_updated') {
            if (!data.post) return;

            const updatedPostId = data.post.id ?? data.post.idPost;
            if (!updatedPostId || !POSTS_DATA[updatedPostId]) return;

            POSTS_DATA[updatedPostId] = {
                ...POSTS_DATA[updatedPostId],
                ...data.post
            };

            refreshPostCard(POSTS_DATA[updatedPostId]);

            const modal = document.getElementById('viewModal');
            if (modal && modal.classList.contains('open')) {
                openViewModal(updatedPostId);
            }
        }

        if (data.type === 'comment_deleted') {
            if (!POSTS_DATA[data.postId]) return;

            POSTS_DATA[data.postId].comments = POSTS_DATA[data.postId].comments.filter(
                c => c.id !== data.commentId
            );
            POSTS_DATA[data.postId].commentsCount = data.commentsCount;

            const card = document.querySelector(`.post-card[data-id="${data.postId}"]`);
            const countBadge = card ? card.querySelector('.comments-count') : null;

            if (countBadge) {
                countBadge.textContent = `💬 ${data.commentsCount}`;
            }

            const modal = document.getElementById('viewModal');
            if (modal && modal.classList.contains('open')) {
                const currentPost = POSTS_DATA[data.postId];
                const titleEl = document.querySelector('#viewModalContent .view-modal-title');

                if (titleEl && currentPost && titleEl.textContent.trim() === currentPost.title) {
                    openViewModal(data.postId);
                }
            }
        }

        if (data.type === 'comment_updated') {
            if (!POSTS_DATA[data.postId]) return;

            POSTS_DATA[data.postId].comments = POSTS_DATA[data.postId].comments.map(c =>
                c.id === data.comment.id
                    ? { ...c, content: data.comment.content, createdAt: data.comment.createdAt }
                    : c
            );

            const modal = document.getElementById('viewModal');
            if (modal && modal.classList.contains('open')) {
                const currentPost = POSTS_DATA[data.postId];
                const titleEl = document.querySelector('#viewModalContent .view-modal-title');

                if (titleEl && currentPost && titleEl.textContent.trim() === currentPost.title) {
                    openViewModal(data.postId);
                }
            }
        }
    };

    eventSource.onerror = (error) => {
        console.error(`Mercure error for post ${postId}:`, error);
    };
}

function subscribeRealtime() {
    Object.keys(POSTS_DATA).forEach((postId) => {
        subscribeToPost(postId);
    });
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

function renderReactionsList(reactions, filter = 'ALL') {
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

function renderReactionFilters(reactions) {
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
            renderReactionsList(reactions, button.dataset.filter);

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

async function openReactionsModal(postId) {
    const list = document.getElementById('reactionsList');
    const filters = document.getElementById('reactionsFilters');

    if (list) list.innerHTML = `<p style="color:#888;">Loading...</p>`;
    if (filters) filters.innerHTML = '';

    document.getElementById('reactionsModal').classList.add('open');
    document.body.style.overflow = 'hidden';

    try {
        const response = await fetch(`/forum/reactions/${postId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        console.log('REACTIONS RAW RESPONSE:', raw);

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
        renderReactionFilters(reactions);
        renderReactionsList(reactions, 'ALL');
    } catch (error) {
        console.error(error);
        if (list) list.innerHTML = `<p style="color:red;">Error loading reactions.</p>`;
    }
}
async function translatePost(postId, language) {
    const box = document.getElementById(`translated-post-box-${postId}`);
    const label = document.getElementById(`translated-post-label-${postId}`);
    const content = document.getElementById(`translated-post-content-${postId}`);

    if (!box || !label || !content) return;

    box.style.display = 'block';
    label.textContent = 'Translating...';
    content.textContent = '';

    try {
        const formData = new FormData();
        formData.append('language', language);

        const response = await fetch(`/forum/translate/${postId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        console.log('TRANSLATE RAW RESPONSE:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.error('Invalid JSON response:', raw);
            label.textContent = 'Translation failed';
            content.textContent = 'Server returned invalid JSON.';
            return;
        }

        if (!data.success) {
            label.textContent = 'Translation failed';
            content.textContent = data.message || 'Unable to translate this post.';
            return;
        }

        const langNames = {
            en: 'English',
            fr: 'French',
            ar: 'Arabic'
        };

        label.textContent = `Translated to ${langNames[data.language] || data.language}`;
        content.textContent = data.translatedText || '';
    } catch (error) {
        console.error(error);
        label.textContent = 'Translation failed';
        content.textContent = 'An error occurred while translating.';
    }
}
async function getCropAdvice() {
    const contentField = document.querySelector('#newPostModal textarea[name="content"]');
    const adviceBox = document.getElementById('cropAdviceBox');
    const adviceLabel = document.getElementById('cropAdviceLabel');
    const adviceContent = document.getElementById('cropAdviceContent');

    if (!contentField || !adviceBox || !adviceLabel || !adviceContent) return;

    const question = contentField.value.trim();

    if (!question) {
        adviceBox.style.display = 'block';
        adviceLabel.textContent = 'Error';
        adviceContent.textContent = 'Please write your farming question in the content field first.';
        return;
    }

    adviceBox.style.display = 'block';
    adviceLabel.textContent = 'Getting advice...';
    adviceContent.textContent = '';

    try {
        const formData = new FormData();
        formData.append('question', question);

        const response = await fetch('/forum/crop-advice', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        console.log('CROP ADVICE RAW RESPONSE:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            adviceLabel.textContent = 'Error';
            adviceContent.textContent = 'Server returned invalid JSON.';
            return;
        }

        if (!data.success) {
            adviceLabel.textContent = 'Error';
            adviceContent.textContent = data.message || 'Failed to get crop advice.';
            return;
        }

        adviceLabel.textContent = 'AI Crop Advice';
        adviceContent.textContent = data.advice || '';
    } catch (error) {
        console.error(error);
        adviceLabel.textContent = 'Error';
        adviceContent.textContent = 'An error occurred while getting crop advice.';
    }
}

function insertCropAdviceIntoContent() {
    const contentField = document.querySelector('#newPostModal textarea[name="content"]');
    const adviceContent = document.getElementById('cropAdviceContent');

    if (!contentField || !adviceContent) return;

    const advice = adviceContent.textContent.trim();
    if (!advice) return;

    if (contentField.value.trim()) {
        contentField.value = contentField.value.trim() + "\n\nAI Crop Advice:\n" + advice;
    } else {
        contentField.value = advice;
    }

    contentField.focus();
}
async function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');

    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
        return;
    }

    dropdown.style.display = 'block';

    const list = document.getElementById('notificationsList');
    list.innerHTML = 'Loading...';

    try {
        const res = await fetch('/notifications/json');
        const data = await res.json();

        if (!data.success) {
            list.innerHTML = 'Error loading notifications';
            return;
        }

        if (data.notifications.length === 0) {
            list.innerHTML = '<p style="color:#888;">No notifications</p>';
            return;
        }

        list.innerHTML = data.notifications.map(n => `
            <div style="
                padding:8px;
                border-bottom:1px solid #eee;
                cursor:pointer;
                background:${n.isRead ? '#fff' : '#f0f9ff'};
            "
            onclick="openNotification(${n.id}, ${n.postId})">
                <div style="font-size:14px;">${n.message}</div>
                <div style="font-size:12px;color:#888;">${n.createdAt}</div>
            </div>
        `).join('');

    } catch (e) {
        list.innerHTML = 'Error';
    }
}
async function openNotification(id, postId) {
    await fetch(`/notifications/read/${id}`, {
        method: 'POST'
    });

    window.location.href = `/forum?highlightPost=${postId}`;
}
async function suggestTitleAI() {
    const contentField = document.querySelector('#newPostModal textarea[name="content"]');
    const titleInput = document.getElementById('postTitleInput');

    if (!contentField || !titleInput) return;

    const content = contentField.value.trim();

    if (!content) {
        alert('Write your content first.');
        return;
    }

    titleInput.value = 'Generating...';

    try {
        const formData = new FormData();
        formData.append('content', content);

        const response = await fetch('/forum/suggest-title', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        console.log('TITLE AI RAW:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            titleInput.value = '';
            alert('Invalid AI response');
            return;
        }

        if (!data.success) {
            titleInput.value = '';
            alert(data.message || 'AI failed');
            return;
        }

        titleInput.value = data.title;

    } catch (error) {
        console.error(error);
        titleInput.value = '';
        alert('Error generating title');
    }
}
async function fixGrammarAI() {
    const textarea = document.getElementById('postContentInput');

    if (!textarea) return;

    const content = textarea.value.trim();

    if (!content) {
        alert('Write content first.');
        return;
    }

    textarea.value = 'Fixing grammar...';

    try {
        const formData = new FormData();
        formData.append('content', content);

        const response = await fetch('/forum/fix-grammar', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await response.text();
        console.log('GRAMMAR RAW:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            alert('Invalid AI response');
            textarea.value = content;
            return;
        }

        if (!data.success) {
            alert(data.message || 'Grammar correction failed');
            textarea.value = content;
            return;
        }

        textarea.value = data.content;

    } catch (error) {
        console.error(error);
        alert('Error fixing grammar');
        textarea.value = content;
    }
}
async function generatePostImageAI() {
    const content = document.getElementById('postContentInput')?.value.trim();
    const title = document.getElementById('postTitleInput')?.value.trim();

    const previewBox = document.getElementById('generatedImageBox');
    const previewImg = document.getElementById('generatedImagePreview');
    const hiddenInput = document.getElementById('generatedImage');
    const fileInput = document.getElementById('postImageInput');

    if (!content) {
        alert('Write content first.');
        return;
    }

    if (!previewBox || !previewImg || !hiddenInput) {
        alert('Generated image elements are missing in HTML.');
        return;
    }

    previewBox.style.display = 'block';
    previewImg.removeAttribute('src');
    previewImg.alt = 'Generating...';
    hiddenInput.value = '';

    try {
        const formData = new FormData();
        formData.append('content', content);
        formData.append('title', title);

        const res = await fetch('/forum/generate-image', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const raw = await res.text();
        console.log('GENERATE IMAGE RAW:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.error('Invalid JSON:', raw);
            alert('Server returned invalid JSON for image generation.');
            return;
        }

        if (!data.success) {
            alert(data.message || 'Failed to generate image');
            return;
        }

        previewImg.src = '/' + data.path;
        previewImg.alt = 'Generated image';
        hiddenInput.value = data.filename;

        if (fileInput) {
            fileInput.value = '';
        }
    } catch (e) {
        console.error(e);
        alert('Error generating image');
    }
}
function selectPostCategory(value, button) {
    const input = document.getElementById('postCategoryInput');
    if (input) {
        input.value = value;
    }

    document.querySelectorAll('.cat-pill-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    if (button) {
        button.classList.add('active');
    }
}