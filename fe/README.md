# Helpdesk Frontend

Frontend Vue 3 untuk Helpdesk System.

## 🛠️ Tech Stack

- Vue 3 + Composition API
- Vite
- Pinia (State Management)
- TailwindCSS
- Vue Router

## 🚀 Setup

```bash
npm install
npm run dev
```

## 📦 Build

```bash
npm run build
```

## 📁 Structure

```
src/
├── stores/       # 21 Pinia stores
├── views/        # Vue components
├── components/   # Reusable components
├── plugins/      # Axios config
├── router/       # Vue Router
└── helpers/      # Utilities
```

## 🔗 API Configuration

Edit `.env`:
```
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

## 🔐 Features

- Role-based access control
- Permission-based menu filtering
- Toast notifications
- Responsive design
