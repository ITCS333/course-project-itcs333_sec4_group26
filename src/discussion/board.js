// --- Global Data Store ---
let topics = [];

// --- Element Selections ---
const newTopicForm = document.querySelector('#new-topic-form');
const topicListContainer = document.querySelector('#topic-list-container');

// --- Functions ---
function createTopicArticle(topic) {
  const article = document.createElement('article');
  article.classList.add('topic');

  const mainLink = document.createElement('a');
  mainLink.href = `topic.html?id=${topic.id}`;
  mainLink.textContent = topic.subject;
  mainLink.classList.add('topic-link');

  const messagePara = document.createElement('p');
  messagePara.textContent = topic.message;

  const footer = document.createElement('footer');
  footer.textContent = `Author: ${topic.author} | Date: ${topic.date}`;

  const actionsDiv = document.createElement('div');
  actionsDiv.classList.add('actions');

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = topic.id;

  actionsDiv.appendChild(editBtn);
  actionsDiv.appendChild(deleteBtn);

  article.appendChild(mainLink);
  article.appendChild(messagePara);
  article.appendChild(footer);
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
    subject: subjectInput.value.trim(),
    message: messageInput.value.trim(),
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
    topics = topics.filter(topic => topic.id !== id);
    renderTopics();
  }
}

async function loadAndInitialize() {
  try {
    const response = await fetch('topics.json');
    topics = await response.json();
    renderTopics();

    newTopicForm.addEventListener('submit', handleCreateTopic);
    topicListContainer.addEventListener('click', handleTopicListClick);
  } catch (error) {
    console.error('Failed to load topics:', error);
  }
}

// --- Initial Page Load ---
loadAndInitialize();
