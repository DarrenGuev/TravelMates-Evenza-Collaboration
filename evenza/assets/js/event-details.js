(function() {
    'use strict';

    const ollamaConfig = {
        baseUrl: 'http://localhost:11434',
        model: 'qwen3:0.6b',
        apiEndpoint: '/api/generate'
    };

    let eventContext = {};
    let conversationHistory = [];

    function initializeEventContext() {
        const eventDataElement = document.getElementById('eventData');
        if (eventDataElement) {
            try {
                eventContext = JSON.parse(eventDataElement.textContent);
            } catch (e) {
                console.error('Failed to parse event data:', e);
            }
        }
    }

    function buildContextPrompt() {
        let context = 'You are a helpful and friendly AI assistant for an event reservation platform called EVENZA. ';
        context += 'Your role is to answer questions about events and help users with their inquiries.\n\n';
        
        context += '=== EVENT INFORMATION ===\n';
        if (eventContext.title) {
            context += `Event Title: ${eventContext.title}\n`;
        }
        if (eventContext.description) {
            context += `Description: ${eventContext.description}\n`;
        }
        if (eventContext.venue) {
            context += `Venue: ${eventContext.venue}\n`;
        }
        if (eventContext.venueAddress) {
            context += `Venue Address: ${eventContext.venueAddress}\n`;
        }
        if (eventContext.formattedDate) {
            context += `Date: ${eventContext.formattedDate}\n`;
        }
        if (eventContext.eventTime) {
            context += `Time: ${eventContext.eventTime}\n`;
        }
        
        if (eventContext.packages && eventContext.packages.length > 0) {
            context += '\n=== AVAILABLE PACKAGES ===\n';
            eventContext.packages.forEach((pkg, index) => {
                context += `${index + 1}. ${pkg.name}: ₱${pkg.price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}\n`;
            });
            context += 'All packages are flat rates for the entire event.\n';
        }
        
        if (eventContext.faqs && eventContext.faqs.length > 0) {
            context += '\n=== FREQUENTLY ASKED QUESTIONS ===\n';
            eventContext.faqs.forEach((faq, index) => {
                context += `Q${index + 1}: ${faq.question}\n`;
                context += `A${index + 1}: ${faq.answer}\n\n`;
            });
        }
        
        context += '\n=== CRITICAL INSTRUCTIONS ===\n';
        context += 'IMPORTANT: Only provide information when explicitly asked. Do NOT volunteer information unless the user asks for it.\n\n';
        context += '1. For GREETINGS ONLY (e.g., "Hello", "Hi", "How are you?", "Good morning", "Hey"):\n';
        context += '   - Respond with a warm, friendly greeting ONLY\n';
        context += '   - Do NOT provide any event information, location, pricing, or other details\n';
        context += '   - Simply greet back and offer to help (e.g., "Hello! How can I help you today?" or "Hi there! I\'m here to help with any questions about this event.")\n';
        context += '   - Wait for the user to ask a specific question before providing any event details\n\n';
        context += '2. For PRICING questions (e.g., "What is the price?", "How much does it cost?", "What packages are available?"):\n';
        context += '   - List the available packages with their prices clearly\n';
        context += '   - Format: Package Name: ₱Price\n\n';
        context += '3. For LOCATION/VENUE questions (e.g., "Where is it?", "What is the venue?", "Location?"):\n';
        context += '   - Provide the venue name and full address\n\n';
        context += '4. For FAQ questions (cancellation, refund, parking, what to bring, what is included):\n';
        context += '   - Use the exact answers provided in the FAQ section above\n\n';
        context += '5. General guidelines:\n';
        context += '   - Be concise, friendly, and professional\n';
        context += '   - Keep responses to 2-3 sentences when possible\n';
        context += '   - Only answer what is asked - do not add extra information unless requested\n';
        context += '   - If asked about something not covered, suggest contacting support at info@evenza.com\n\n';
        
        return context;
    }

    function addUserMessage(message) {
        const chatBox = document.querySelector('.ai-chat-box');
        if (!chatBox) return;

        const userMessage = document.createElement('div');
        userMessage.className = 'user-message';
        userMessage.innerHTML = '<p><strong>You:</strong> ' + escapeHtml(message) + '</p>';
        chatBox.appendChild(userMessage);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function addAiMessage(message, isLoading = false) {
        const chatBox = document.querySelector('.ai-chat-box');
        if (!chatBox) return;

        const aiMessage = document.createElement('div');
        aiMessage.className = 'ai-message';
        if (isLoading) {
            aiMessage.id = 'aiLoadingIndicator';
            aiMessage.innerHTML = '<p><em>AI is thinking</em></p>';
        } else {
            aiMessage.innerHTML = '<p><strong>AI:</strong> ' + escapeHtml(message) + '</p>';
        }
        chatBox.appendChild(aiMessage);
        chatBox.scrollTop = chatBox.scrollHeight;
        return aiMessage;
    }

    function removeLoadingIndicator() {
        const loadingIndicator = document.getElementById('aiLoadingIndicator');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function enhanceUserQuestion(question) {
        const lowerQuestion = question.toLowerCase().trim();
        
        let questionContext = '';
        
        if (lowerQuestion.match(/^(hi|hello|hey|greetings|how are you|good morning|good afternoon|good evening|what's up|sup)/)) {
            questionContext = '[CRITICAL: This is ONLY a greeting. Respond with a friendly greeting ONLY. Do NOT provide any event information, location, pricing, or other details. Just greet back and offer to help.]\n\n';
        } else if (lowerQuestion.match(/(price|cost|pricing|how much|package|packages)/)) {
            questionContext = '[This is a pricing inquiry. Provide a clear list of available packages with prices.]\n\n';
        } else if (lowerQuestion.match(/(location|venue|where|address|place)/)) {
            questionContext = '[This is a location inquiry. Provide the venue name and full address.]\n\n';
        } else if (lowerQuestion.match(/(cancel|cancellation|refund|refunds)/)) {
            questionContext = '[This is about cancellation/refund policy. Use the FAQ answer provided.]\n\n';
        } else if (lowerQuestion.match(/(parking|park|car|vehicle)/)) {
            questionContext = '[This is about parking. Use the FAQ answer provided.]\n\n';
        } else if (lowerQuestion.match(/(bring|what to bring|what should i|items|materials)/)) {
            questionContext = '[This is about what to bring. Use the FAQ answer provided.]\n\n';
        } else if (lowerQuestion.match(/(included|include|what.*included|ticket.*include)/)) {
            questionContext = '[This is about what is included. Use the FAQ answer provided.]\n\n';
        }
        
        return questionContext + question;
    }

    async function callOllamaApi(userQuestion) {
        const enhancedQuestion = enhanceUserQuestion(userQuestion);
        const fullPrompt = buildContextPrompt() + 'User Question: ' + enhancedQuestion + '\n\nAI Response:';
        
        try {
            const response = await fetch(`${ollamaConfig.baseUrl}${ollamaConfig.apiEndpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    model: ollamaConfig.model,
                    prompt: fullPrompt,
                    stream: false
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data.response || 'I apologize, but I couldn\'t generate a response. Please try again.';
        } catch (error) {
            console.error('Ollama API error:', error);
            throw error;
        }
    }

    window.askAI = async function() {
        const questionInput = document.getElementById('aiQuestion');
        const sendButton = document.getElementById('aiSendButton');
        
        if (!questionInput) return;
        
        const question = questionInput.value.trim();
        if (!question) return;

        questionInput.disabled = true;
        if (sendButton) sendButton.disabled = true;

        addUserMessage(question);
        
        questionInput.value = '';

        const loadingMessage = addAiMessage('', true);

        try {
            const aiResponse = await callOllamaApi(question);
            
            removeLoadingIndicator();
            
            addAiMessage(aiResponse);
            
            conversationHistory.push({
                user: question,
                ai: aiResponse
            });
        } catch (error) {
            removeLoadingIndicator();
            
            addAiMessage('Sorry, I encountered an error connecting to the AI service. Please make sure Ollama is running on localhost:11434 and try again later.');
            console.error('Error calling Ollama:', error);
        } finally {
            questionInput.disabled = false;
            if (sendButton) sendButton.disabled = false;
            questionInput.focus();
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        initializeEventContext();
        
        const aiQuestionInput = document.getElementById('aiQuestion');
        const sendButton = document.getElementById('aiSendButton');
        
        if (aiQuestionInput) {
            aiQuestionInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    askAI();
                }
            });
        }

        if (sendButton) {
            sendButton.addEventListener('click', function(e) {
                e.preventDefault();
                askAI();
            });
        }
    });

})();
