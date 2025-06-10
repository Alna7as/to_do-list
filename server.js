const express = require('express');
const session = require('express-session');
const bodyParser = require('body-parser');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(session({ secret: 'todo-secret', resave: false, saveUninitialized: true }));

const DATA_FILE = 'data.json';
let data = { users: {} };
if (fs.existsSync(DATA_FILE)) {
  try {
    data = JSON.parse(fs.readFileSync(DATA_FILE));
  } catch (e) {
    console.error('Could not read data file', e);
  }
}

function saveData() {
  fs.writeFileSync(DATA_FILE, JSON.stringify(data, null, 2));
}

app.use(express.static('public'));

app.post('/register', (req, res) => {
  const { username, password } = req.body;
  if (!username || !password) {
    return res.status(400).json({ error: 'Missing username or password' });
  }
  if (data.users[username]) {
    return res.status(400).json({ error: 'User already exists' });
  }
  data.users[username] = { password, tasks: [] };
  saveData();
  req.session.user = username;
  res.json({ success: true });
});

app.post('/login', (req, res) => {
  const { username, password } = req.body;
  const user = data.users[username];
  if (!user || user.password !== password) {
    return res.status(401).json({ error: 'Invalid credentials' });
  }
  req.session.user = username;
  res.json({ success: true });
});

function ensureAuth(req, res, next) {
  if (!req.session.user) {
    return res.status(401).json({ error: 'Not authenticated' });
  }
  next();
}

app.get('/tasks', ensureAuth, (req, res) => {
  const user = data.users[req.session.user];
  res.json(user.tasks);
});

app.post('/tasks', ensureAuth, (req, res) => {
  const { text } = req.body;
  if (!text) {
    return res.status(400).json({ error: 'Task text missing' });
  }
  const user = data.users[req.session.user];
  const task = { id: Date.now(), text, done: false };
  user.tasks.push(task);
  saveData();
  res.json(task);
});

app.delete('/tasks/:id', ensureAuth, (req, res) => {
  const id = parseInt(req.params.id, 10);
  const user = data.users[req.session.user];
  user.tasks = user.tasks.filter(t => t.id !== id);
  saveData();
  res.json({ success: true });
});

app.patch('/tasks/:id', ensureAuth, (req, res) => {
  const id = parseInt(req.params.id, 10);
  const { text, done } = req.body;
  const user = data.users[req.session.user];
  const task = user.tasks.find(t => t.id === id);
  if (!task) {
    return res.status(404).json({ error: 'Task not found' });
  }
  if (typeof text === 'string') {
    task.text = text;
  }
  if (typeof done === 'boolean') {
    task.done = done;
  }
  saveData();
  res.json(task);
});

app.post('/logout', (req, res) => {
  req.session.destroy(() => {
    res.json({ success: true });
  });
});

app.listen(PORT, () => console.log(`Server listening on port ${PORT}`));
