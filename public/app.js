function showLogin() {
  document.getElementById('login').style.display = 'block';
  document.getElementById('register').style.display = 'none';
  document.getElementById('app').style.display = 'none';
}
function showRegister() {
  document.getElementById('login').style.display = 'none';
  document.getElementById('register').style.display = 'block';
  document.getElementById('app').style.display = 'none';
}
function showApp() {
  document.getElementById('login').style.display = 'none';
  document.getElementById('register').style.display = 'none';
  document.getElementById('app').style.display = 'block';
}

async function register() {
  const username = document.getElementById('register-username').value;
  const password = document.getElementById('register-password').value;
  const res = await fetch('/register', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });
  if (res.ok) {
    loadTasks();
    showApp();
  } else {
    alert('Registration failed');
  }
}

async function login() {
  const username = document.getElementById('login-username').value;
  const password = document.getElementById('login-password').value;
  const res = await fetch('/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });
  if (res.ok) {
    loadTasks();
    showApp();
  } else {
    alert('Login failed');
  }
}

async function logout() {
  await fetch('/logout', { method: 'POST' });
  showLogin();
}

async function loadTasks() {
  const res = await fetch('/tasks');
  if (!res.ok) return;
  const tasks = await res.json();
  const ul = document.getElementById('tasks');
  ul.innerHTML = '';
  tasks.forEach(task => {
    const li = document.createElement('li');

    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.checked = task.done;
    cb.onchange = async () => {
      await fetch('/tasks/' + task.id, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ done: cb.checked })
      });
      loadTasks();
    };

    const span = document.createElement('span');
    span.textContent = task.text;
    if (task.done) span.style.textDecoration = 'line-through';
    span.ondblclick = async () => {
      const newText = prompt('Edit task', task.text);
      if (newText) {
        await fetch('/tasks/' + task.id, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ text: newText })
        });
        loadTasks();
      }
    };

    const btn = document.createElement('button');
    btn.textContent = 'Delete';
    btn.onclick = async () => {
      await fetch('/tasks/' + task.id, { method: 'DELETE' });
      loadTasks();
    };

    li.appendChild(cb);
    li.appendChild(span);
    li.appendChild(btn);
    ul.appendChild(li);
  });
}

async function addTask() {
  const text = document.getElementById('new-task').value;
  if (!text) return;
  const res = await fetch('/tasks', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ text })
  });
  if (res.ok) {
    document.getElementById('new-task').value = '';
    loadTasks();
  }
}

showLogin();
