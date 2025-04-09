# Check if Python is installed
try {
    python -V
}
catch {
    Write-Host "Python is not installed or not in PATH." -ForegroundColor Red
    Write-Host "Please install Python and try again." -ForegroundColor Red
    exit 1
}

# Check if required Python packages are installed
try {
    pip install -r requirements.txt
    Write-Host "Python dependencies installed." -ForegroundColor Green
}
catch {
    Write-Host "Failed to install Python dependencies. Please check the error above." -ForegroundColor Red
    exit 1
}

# Check if Ollama is running
try {
    $response = Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get -ErrorAction Stop
    Write-Host "Ollama is running. Available models: " -ForegroundColor Green
    foreach ($model in $response.models) {
        Write-Host "  - $($model.name)" -ForegroundColor Cyan
    }
}
catch {
    Write-Host "Ollama is not running or not responding." -ForegroundColor Red
    Write-Host "Please start Ollama and try again." -ForegroundColor Red
    exit 1
}

# Start the Flask API server
Write-Host "Starting Ollama API server on port 5000..." -ForegroundColor Yellow
python ollama_api.py 