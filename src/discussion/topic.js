// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = []; // Will hold replies for *this* topic

// --- Element Selections ---
const topicSubject = document.getElementById('topic-subject');
const opMessage = document.getElementById('op-message');
const opFooter = document.getElementById('op-footer');
const replyListContainer = document.getElementById('reply-list-container');
const replyForm = document.getElementById('reply-form');
const newReplyText = document.getElementById('new-reply');

// --- Functions ---
function getTopicIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

function renderOriginalPost(topic) {
    topicSubject.textContent = topic.subject;
    opMessage.textContent = topic.message;
    opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;
}

function createReplyArticle(reply) {
    const article = document.createElement('article');
    article.classList.add('reply');

    const p = document.createElement('p');
    p.textContent = reply.text;
    article.appendChild(p);

    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${reply.author} on ${reply.date}`;
    article.appendChild(footer);

    const actions = document.createElement('div');
    actions.classList.add('actions');

    const deleteBtn = document.createElement('button');
    deleteBtn.classList.add('delete-reply-btn');
    deleteBtn.dataset.id = reply.id;
    deleteBtn.textContent = 'Delete';

    actions.appendChild(deleteBtn);
    article.appendChild(actions);

    return article;
}

function renderReplies() {
    replyListContainer.innerHTML = '';
    currentReplies.forEach(reply => {
        const article = createReplyArticle(reply);
        replyListContainer.appendChild(article);
    });
}

function handleAddReply(event) {
    event.preventDefault();
    const text = newReplyText.value.trim();
    if (!text) return;

    const newReply = {
        id: `reply_${Date.now()}`,
        author: 'Student',
        date: new Date().toISOString().split('T')[0],
        text: text
    };

    currentReplies.push(newReply);
    renderReplies();
    newReplyText.value = '';
}

function handleReplyListClick(event) {
    if (event.target.classList.contains('delete-reply-btn')) {
        const id = event.target.dataset.id;
        currentReplies = currentReplies.filter(reply => reply.id !== id);
        renderReplies();
    }
}

async function initializePage() {
    currentTopicId = getTopicIdFromURL();
    if (!currentTopicId) {
        topicSubject.textContent = "Topic not found.";
        return;
    }

    try {
        const [topicsRes, repliesRes] = await Promise.all([
            fetch('topics.json'),
            fetch('replies.json')
        ]);

        const topics = await topicsRes.json();
        const repliesData = await repliesRes.json();

        const topic = topics.find(t => t.id === currentTopicId);
        currentReplies = repliesData[currentTopicId] || [];

        if (topic) {
            renderOriginalPost(topic);
            renderReplies();

            replyForm.addEventListener('submit', handleAddReply);
            replyListContainer.addEventListener('click', handleReplyListClick);
        } else {
            topicSubject.textContent = "Topic not found.";
        }
    } catch (err) {
        console.error(err);
        topicSubject.textContent = "Error loading topic.";
    }
}

// --- Initial Page Load ---
initializePage();
