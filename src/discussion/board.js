// --- Global Data Store ---
let topics = [];

// --- Element Selections ---
let newTopicForm;
let topicListContainer;

// --- Functions ---
function createTopicArticle(topic) {
  const article = document.createElement('article');

  // العنوان مع الرابط
  const h2 = document.createElement('h2');
  const link = document.createElement('a');
  link.href = `topic.html?id=${topic.id}`;
  link.textContent = topic.subject;
  h2.appendChild(link);
  article.appendChild(h2);

  // الرسالة
  if (topic.message) {
    const p = document.createElement('p');
    p.textContent = topic.message;
    article.appendChild(p);
  }

  // الفوتر: المؤلف والتاريخ
  const footer = document.createElement('footer');
  footer.textContent = `by ${topic.author} on ${topic.date}`;
  article.appendChild(footer);

  // div للأكشنز
  const actionsDiv = document.createElement('div');
  actionsDiv.className = 'actions';

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.className = 'edit-btn';
  editBtn.dataset.id = topic.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'delete-btn';
  deleteBtn.dataset.id = topic.id;

  actionsDiv.appendChild(editBtn);
  actionsDiv.appendChild(deleteBtn);
  article.appendChild(actionsDiv);

  return article;
}

function renderTopics() {
  topicListContainer.innerHTML = '';
  topics.forEach(topic => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

function handleCreateTopic(event) {
  event.preventDefault();
  const subjectInput = document.querySelector('#topic-subject');
  const messageInput = document.querySelector('#topic-message');

  const newTopic = {
    id: `topic_${Date.now()}`,
    subject: subjectInput.value,
    message: messageInput.value,
    author: 'Student',
    date: new Date().toISOString().split('T')[0]
  };

  topics.push(newTopic);
  renderTopics();
  newTopicForm.reset();
}

function handleTopicListClick(event) {
  if (event.target.classList.contains('delete-btn')) {
    const id = event.target.dataset.id;
    topics = topics.filter(t => t.id !== id);
    renderTopics();
  }
}

async function loadAndInitialize() {
  newTopicForm = document.querySelector('#new-topic-form');
  topicListContainer = document.querySelector('#topic-list-container');

  // تحميل JSON
  try {
    const response = await fetch('topics.json');
    const data = await response.json();
    topics = data;
  } catch (err) {
    console.error('Error loading topics.json:', err);
  }

  renderTopics();

  newTopicForm.addEventListener('submit', handleCreateTopic);
  topicListContainer.addEventListener('click', handleTopicListClick);
}

// --- Initial Page Load ---
loadAndInitialize();
