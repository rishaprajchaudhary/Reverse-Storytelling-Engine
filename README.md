# Reverse Storytelling Engine

A web-based application that generates creative stories from just a title using AI. Built with React.js, PHP, and MySQL.

## Features

- **AI Story Generation**: Create unique stories by simply entering a title
- **User Authentication**: Register and login to save and manage your stories
- **Modern UI/UX**: Responsive design with Material UI components
- **Story Management**: Save, edit, and share your generated stories
- **Markdown Support**: Stories are rendered with Markdown for rich formatting

## Tech Stack

### Frontend
- React.js
- Material UI
- React Router
- Axios for API calls
- React Markdown for content rendering

### Backend
- PHP for API endpoints
- MySQL database for data storage
- RESTful API architecture

## Setup Instructions

### Database Setup
1. Create a MySQL database
2. Import the database schema from `server/db_setup.sql`
3. Update database credentials in `server/config.php`

### Backend Setup
1. Place the server directory in your PHP-enabled web server (Apache, Nginx, etc.)
2. Ensure PHP is configured with MySQL support
3. Update CORS settings in `.htaccess` if needed

### Frontend Setup
1. Navigate to the client directory
2. Install dependencies:
   ```
   npm install
   ```
3. Update API base URL in `client/src/services/api.js` if needed
4. Start the development server:
   ```
   npm start
   ```

## Project Structure

```
project_mysql/
├── client/                # React frontend
│   ├── public/            # Static files
│   └── src/
│       ├── components/    # Reusable components
│       ├── pages/         # Page components
│       └── services/      # API services
├── server/                # PHP backend
│   ├── auth.php           # Authentication endpoints
│   ├── story.php          # Story management endpoints
│   ├── ai.php             # AI integration
│   └── config.php         # Database configuration
└── README.md              # This file
```

## API Endpoints

### Authentication
- `POST /auth.php?action=signup` - Register a new user
- `POST /auth.php?action=login` - Login a user

### Stories
- `POST /story.php?action=generate` - Generate a new story using AI
- `GET /story.php?action=get&story_id={id}` - Get a story by ID
- `GET /story.php?action=list` - List stories with optional filters
- `POST /story.php?action=save` - Save a new or update an existing story

### AI
- `POST /ai.php` - Generate text using AI based on a prompt

## License

MIT License

## Acknowledgements

- All the contributors to the open-source libraries used in this project
- The AI community for inspiration and research that made this possible 