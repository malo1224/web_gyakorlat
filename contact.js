document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('submitBtn');
    const btnText = btn.querySelector('.btn-text');
    const loader = document.getElementById('loader');
    const responseMsg = document.getElementById('responseMessage');
    const messageText = document.getElementById("message");

    btnText.style.display = 'none';
    loader.style.display = 'block';
    btn.disabled = true;

    try {
        const response = await fetch('http://localhost/api.php?type=messages', {
            method: 'POST',
            headers: {
                Authorization: sessionStorage.getItem("token"),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: messageText.value })
        });
    
        const result = await response.json();
    
        loader.style.display = 'none';
        btnText.style.display = 'block';
        btn.disabled = false;

        responseMsg.textContent = "Köszönjük! Az üzenetet sikeresen továbbítottuk.";
        responseMsg.className = "success";
        
        this.reset();

    }
    catch (err) {
        console.error(err);
    }
});

async function fetchMessages() {
    try {
        const response = await fetch('http://localhost/api.php?type=messages');
        const messages = await response.json();

        const chatBox = document.getElementById('chat-box');
        chatBox.innerHTML = '';

        messages.forEach(msg => {
            const messageElement = document.createElement('div');
            messageElement.className = 'message-item';
            
            const senderClass = msg.sender === 'Anonymous' ? 'text-muted' : 'text-primary';

            messageElement.innerHTML = `
                <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 5px; border-radius: 5px;">
                    <strong class="${senderClass}">${msg.sender}</strong> 
                    <small style="color: gray; float: right;">${msg.created_at}</small>
                    <p style="margin-top: 5px;">${msg.content}</p>
                </div>
            `;
            chatBox.appendChild(messageElement);
        });
    } catch (error) {
        console.error("Hiba az üzenetek betöltésekor:", error);
    }
}

setInterval(fetchMessages, 5000);
fetchMessages();