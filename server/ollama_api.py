import json
import requests
from flask import Flask, request, jsonify
from flask_cors import CORS

# Initialize Flask app
app = Flask(__name__)
CORS(app)  # Enable CORS for all routes

# Ollama API endpoint
OLLAMA_API_URL = "http://localhost:11434/api/generate"
MODEL_NAME = "llama2:7b"  # The model you have installed

@app.route('/generate', methods=['POST'])
def generate_text():
    data = request.json
    if not data or 'prompt' not in data:
        return jsonify({
            "success": False,
            "message": "Please provide a prompt"
        }), 400

    prompt = data['prompt']
    
    try:
        # Prepare the prompt for story generation
        story_prompt = f"Generate a creative and detailed story with the title: '{prompt}'. Make it engaging and interesting."
        
        # Call Ollama API
        response = requests.post(
            OLLAMA_API_URL,
            json={
                "model": MODEL_NAME,
                "prompt": story_prompt,
                "stream": False,
                "options": {
                    "temperature": 0.7,
                    "top_p": 0.9,
                    "max_tokens": 1000
                }
            }
        )
        
        if response.status_code == 200:
            result = response.json()
            # Format the response as Markdown with title
            story = f"# {prompt}\n\n{result['response']}"
            
            return jsonify({
                "success": True,
                "message": "Text generated successfully",
                "data": {
                    "text": story
                }
            })
        else:
            return jsonify({
                "success": False,
                "message": f"Ollama API error: {response.text}"
            }), 500
        
    except Exception as e:
        print(f"Error generating text: {str(e)}")
        return jsonify({
            "success": False,
            "message": f"Error generating text: {str(e)}"
        }), 500

@app.route('/status', methods=['GET'])
def get_status():
    try:
        # Check if Ollama is running by making a simple request
        response = requests.get("http://localhost:11434/api/tags")
        if response.status_code == 200:
            return jsonify({
                "success": True,
                "message": "Ollama API is running",
                "models": response.json()
            })
        else:
            return jsonify({
                "success": False,
                "message": "Ollama API returned an error"
            }), 500
    except Exception as e:
        return jsonify({
            "success": False,
            "message": f"Error connecting to Ollama: {str(e)}"
        }), 500

if __name__ == '__main__':
    print(f"Starting Ollama API server for {MODEL_NAME}...")
    app.run(host='0.0.0.0', port=5000, debug=False) 