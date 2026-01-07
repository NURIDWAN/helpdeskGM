# Frontend Unit Tests

## Setup

Install dependencies:
```bash
cd fe
npm install
```

## Running Tests

```bash
# Run tests in watch mode
npm test

# Run tests once
npm run test:run

# Run with coverage
npm run test:coverage

# Run with UI
npm run test:ui
```

## Test Structure

```
fe/tests/
├── setup.js                          # Global test setup
├── unit/
│   ├── helpers/
│   │   ├── errorHelper.test.js       # Error handling tests
│   │   ├── format.test.js            # Format helpers tests
│   │   └── permissionHelper.test.js  # Permission helpers tests
│   ├── stores/
│   │   ├── auth.test.js              # Auth store tests
│   │   ├── ticket.test.js            # Ticket store tests
│   │   └── branch.test.js            # Branch store tests
│   └── components/
│       └── (future component tests)
```

## Test Coverage

| File | Tests | Coverage |
|------|-------|----------|
| errorHelper.js | 6 | HTTP error handling |
| format.js | 12 | Currency, date, bytes |
| permissionHelper.js | 12 | can, canOneOf, hasRole |
| auth.js | 9 | Login, logout, checkAuth |
| ticket.js | 15 | CRUD, replies, close |
| branch.js | 8 | CRUD operations |

## Scripts

| Command | Description |
|---------|-------------|
| `npm test` | Watch mode |
| `npm run test:run` | Single run |
| `npm run test:coverage` | With coverage |
| `npm run test:ui` | Vitest UI |
