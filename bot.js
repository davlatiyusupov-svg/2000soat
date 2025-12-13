// Telegram WebApp initialization
let tg = window.Telegram.WebApp;

// Expand the app to full screen
tg.expand();

// Enable closing confirmation
tg.enableClosingConfirmation();

// Get user data from Telegram
let user = tg.initDataUnsafe.user;
let userId = user.id;
let userName = user.first_name || "Foydalanuvchi";

// Timer variables
let activeTimers = {};
let currentTimerType = null;
let startTime = null;

// Initialize the app
function initApp() {
    // Load user data
    loadUserData();
    
    // Load content
    loadDailyContent();
    
    // Set up event listeners
    setupEventListeners();
    
    // Update UI with user data
    document.getElementById('userGoal').textContent = localStorage.getItem('userGoal') || 'Kasbiy rivojlanish';
}

// Load user data from server
function loadUserData() {
    // Mock data for now - replace with actual API call
    let totalMinutes = parseInt(localStorage.getItem('totalMinutes') || '0');
    let streakDays = parseInt(localStorage.getItem('streakDays') || '0');
    
    updateProgressUI(totalMinutes, streakDays);
}

// Update progress UI
function updateProgressUI(totalMinutes, streakDays) {
    let totalHours = (totalMinutes / 60).toFixed(1);
    let percentage = ((totalMinutes / 120000) * 100).toFixed(1); // 2000 soat = 120000 minut
    
    document.getElementById('currentHours').textContent = `${totalHours} soat / 2000 soat`;
    document.getElementById('progressPercent').textContent = `${percentage}%`;
    document.getElementById('progressFill').style.width = `${Math.min(percentage, 100)}%`;
    document.getElementById('streakDays').textContent = `🔥 ${streakDays} kun`;
}

// Load daily content based on user's goal
function loadDailyContent() {
    let goal = localStorage.getItem('userGoal') || 'kasbiy';
    let dailyHours = parseInt(localStorage.getItem('dailyHours') || '2');
    
    // Calculate time distribution based on daily hours
    let totalMinutes = dailyHours * 60;
    
    // Time distribution for each content type
    let distribution = {
        podcast: Math.floor(totalMinutes * 0.25), // 25%
        book: Math.floor(totalMinutes * 0.25),    // 25%
        article: Math.floor(totalMinutes * 0.15), // 15%
        task: Math.floor(totalMinutes * 0.20),    // 20%
        test: Math.floor(totalMinutes * 0.10),    // 10%
        advice: Math.floor(totalMinutes * 0.05)   // 5%
    };
    
    // Get content based on goal
    let content = getContentByGoal(goal, distribution);
    
    // Render content cards
    renderContentCards(content);
}

// Get content by goal
function getContentByGoal(goal, distribution) {
    // This is mock data - replace with actual content from your database
    let contentMap = {
        'ielts': {
            podcast: {
                title: "IELTS Listening Practice",
                description: "Advanced listening exercises for IELTS",
                link: "https://youtube.com/ielts-practice",
                duration: distribution.podcast
            },
            book: {
                title: "Cambridge IELTS 15",
                description: "Official IELTS preparation book",
                link: "https://example.com/ielts-book.pdf",
                duration: distribution.book
            },
            article: {
                title: "IELTS Writing Tips",
                description: "How to score 7+ in writing",
                link: "https://example.com/ielts-article",
                duration: distribution.article
            },
            task: {
                title: "Write 250-word essay",
                description: "Practice Task 2 writing",
                duration: distribution.task
            },
            test: {
                title: "IELTS Vocabulary Test",
                description: "Test your IELTS vocabulary",
                questions: [
                    {
                        question: "What does 'ubiquitous' mean?",
                        options: ["Rare", "Everywhere", "Small", "Large"],
                        answer: 1
                    }
                ],
                duration: distribution.test
            },
            advice: {
                title: "IELTS Advice",
                text: "Practice speaking with a timer to improve fluency.",
                duration: distribution.advice
            }
        },
        'kasbiy': {
            podcast: {
                title: "Kasbiy ko'nikmalar",
                description: "Zamonaviy kasbiy ko'nikmalarni rivojlantirish",
                link: "https://youtube.com/kasbiy-konikmalar",
                duration: distribution.podcast
            },
            book: {
                title: "Atomiy odatlar",
                description: "James Clearning mashhur kitobi",
                link: "https://example.com/atomic-habits.pdf",
                duration: distribution.book
            },
            article: {
                title: "Vaqt boshqaruv usullari",
                description: "Samarali vaqt boshqaruvi",
                link: "https://example.com/time-management",
                duration: distribution.article
            },
            task: {
                title: "15 daqiqa telefon ishlatma",
                description: "Diqqatni rivojlantirish uchun",
                duration: distribution.task
            },
            test: {
                title: "Kasbiy kompetensiya testi",
                description: "O'z ko'nikmalaringizni baholang",
                questions: [
                    {
                        question: "Qaysi ko'nikam sizda eng kuchli?",
                        options: ["Muloqot", "Texnologiya", "Liderlik", "Ijod"],
                        answer: 0
                    }
                ],
                duration: distribution.test
            },
            advice: {
                title: "Kasbiy maslahat",
                text: "Har kuni yangi narsa o'rganing, hatto kichik bo'lsa ham.",
                duration: distribution.advice
            }
        }
        // Add other goals here...
    };
    
    return contentMap[goal] || contentMap['kasbiy'];
}

// Render content cards
function renderContentCards(content) {
    let grid = document.getElementById('contentGrid');
    grid.innerHTML = '';
    
    // Podcast Card
    grid.appendChild(createCard({
        type: 'podcast',
        icon: '🎧',
        title: content.podcast.title,
        description: content.podcast.description,
        duration: content.podcast.duration,
        link: content.podcast.link
    }));
    
    // Book Card
    grid.appendChild(createCard({
        type: 'book',
        icon: '📚',
        title: content.book.title,
        description: content.book.description,
        duration: content.book.duration,
        link: content.book.link
    }));
    
    // Article Card
    grid.appendChild(createCard({
        type: 'article',
        icon: '📄',
        title: content.article.title,
        description: content.article.description,
        duration: content.article.duration,
        link: content.article.link
    }));
    
    // Task Card
    grid.appendChild(createTaskCard({
        title: content.task.title,
        description: content.task.description
    }));
    
    // Test Card
    grid.appendChild(createTestCard({
        title: content.test.title,
        description: content.test.description,
        questions: content.test.questions
    }));
    
    // Advice Card
    grid.appendChild(createAdviceCard({
        title: content.advice.title,
        text: content.advice.text
    }));
}

// Create content card
function createCard(data) {
    let card = document.createElement('div');
    card.className = 'content-card';
    card.innerHTML = `
        <div class="card-header">
            <div class="card-icon">${data.icon}</div>
            <div class="card-title">${data.title}</div>
            <div class="card-duration">${data.duration} min</div>
        </div>
        <div class="card-desc">${data.description}</div>
        <a href="${data.link}" target="_blank" class="link-btn">📖 O'qishni boshlash</a>
        <div class="timer-controls">
            <button class="timer-btn start" onclick="startTimer('${data.type}')">▶️ START</button>
            <button class="timer-btn stop" onclick="stopTimer('${data.type}')" disabled>⏹️ STOP</button>
            <div class="timer-display" id="${data.type}Timer">00:00</div>
        </div>
    `;
    return card;
}

// Create task card
function createTaskCard(data) {
    let card = document.createElement('div');
    card.className = 'content-card';
    card.innerHTML = `
        <div class="card-header">
            <div class="card-icon">✅</div>
            <div class="card-title">Kunlik task</div>
            <div class="card-duration">Bugun</div>
        </div>
        <div class="task-item">
            <input type="checkbox" class="task-checkbox" id="dailyTask" onchange="completeTask(this)">
            <label class="task-text" for="dailyTask">${data.title}: ${data.description}</label>
        </div>
    `;
    return card;
}

// Create test card
function createTestCard(data) {
    let card = document.createElement('div');
    card.className = 'content-card';
    
    let optionsHTML = '';
    data.questions[0].options.forEach((option, index) => {
        optionsHTML += `
            <div class="test-option" onclick="selectAnswer(this, ${index})">
                ${String.fromCharCode(65 + index)}) ${option}
            </div>
        `;
    });
    
    card.innerHTML = `
        <div class="card-header">
            <div class="card-icon">🧪</div>
            <div class="card-title">${data.title}</div>
            <div class="card-duration">${data.duration} min</div>
        </div>
        <div class="card-desc">${data.description}</div>
        <div class="test-question">${data.questions[0].question}</div>
        <div class="test-options">${optionsHTML}</div>
        <button class="test-submit" onclick="submitTest()">Javob berish</button>
    `;
    
    return card;
}

// Create advice card
function createAdviceCard(data) {
    let card = document.createElement('div');
    card.className = 'content-card';
    card.innerHTML = `
        <div class="card-header">
            <div class="card-icon">💡</div>
            <div class="card-title">${data.title}</div>
            <div class="card-duration">Kunlik</div>
        </div>
        <div class="card-desc">${data.text}</div>
    `;
    return card;
}

// Timer functions
function startTimer(type) {
    startTime = new Date();
    currentTimerType = type;
    
    // Disable all start buttons
    document.querySelectorAll('.timer-btn.start').forEach(btn => {
        btn.disabled = true;
    });
    
    // Enable stop button for this card
    let stopBtn = document.getElementById(type + 'Timer').parentElement.querySelector('.timer-btn.stop');
    stopBtn.disabled = false;
    
    // Send start event to bot
    tg.sendData(JSON.stringify({
        action: 'start_timer',
        user_id: userId,
        content_type: type,
        start_time: startTime.toISOString()
    }));
    
    // Start timer display
    activeTimers[type] = setInterval(() => {
        let elapsed = Math.floor((new Date() - startTime) / 1000);
        let minutes = Math.floor(elapsed / 60);
        let seconds = elapsed % 60;
        document.getElementById(type + 'Timer').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
}

function stopTimer(type) {
    if (!startTime || !activeTimers[type]) return;
    
    let endTime = new Date();
    let duration = Math.floor((endTime - startTime) / 1000); // seconds
    
    clearInterval(activeTimers[type]);
    delete activeTimers[type];
    
    // Re-enable start buttons
    document.querySelectorAll('.timer-btn.start').forEach(btn => {
        btn.disabled = false;
    });
    
    // Disable this stop button
    document.querySelectorAll('.timer-btn.stop').forEach(btn => {
        btn.disabled = true;
    });
    
    // Send stop event to bot
    tg.sendData(JSON.stringify({
        action: 'stop_timer',
        user_id: userId,
        content_type: type,
        duration: duration,
        end_time: endTime.toISOString()
    }));
    
    // Update progress
    updateProgress(duration / 60); // minutes
}

// Update progress
function updateProgress(minutes) {
    let current = parseInt(localStorage.getItem('totalMinutes') || '0');
    current += minutes;
    localStorage.setItem('totalMinutes', current);
    
    // Update streak if this is first activity today
    let lastActive = localStorage.getItem('lastActive');
    let today = new Date().toDateString();
    
    if (lastActive !== today) {
        let streak = parseInt(localStorage.getItem('streakDays') || '0');
        streak++;
        localStorage.setItem('streakDays', streak);
        localStorage.setItem('lastActive', today);
    }
    
    // Update UI
    loadUserData();
}

// Task completion
function completeTask(checkbox) {
    let label = checkbox.nextElementSibling;
    if (checkbox.checked) {
        label.classList.add('task-completed');
        
        // Send task completion to bot
        tg.sendData(JSON.stringify({
            action: 'task_completed',
            user_id: userId,
            task: label.textContent
        }));
        
        // Add to progress (10 minutes for task)
        updateProgress(10);
    } else {
        label.classList.remove('task-completed');
    }
}

// Test functions
let selectedAnswer = null;

function selectAnswer(element, index) {
    // Remove previous selection
    document.querySelectorAll('.test-option').forEach(opt => {
        opt.style.background = '#f5f5f5';
    });
    
    // Select new answer
    element.style.background = '#E3F2FD';
    selectedAnswer = index;
}

function submitTest() {
    if (selectedAnswer === null) {
        alert('Iltimos, javob tanlang!');
        return;
    }
    
    // Send test result to bot
    tg.sendData(JSON.stringify({
        action: 'test_completed',
        user_id: userId,
        answer: selectedAnswer,
        correct: selectedAnswer === 0 // Mock correct answer
    }));
    
    // Add to progress (10 minutes for test)
    updateProgress(10);
    
    alert('Test topshirildi!');
}

// Setup event listeners
function setupEventListeners() {
    // Bottom navigation
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all
            document.querySelectorAll('.nav-btn').forEach(b => {
                b.classList.remove('active');
            });
            
            // Add active to clicked
            this.classList.add('active');
            
            // Handle navigation
            let target = this.getAttribute('href').substring(1);
            handleNavigation(target);
        });
    });
}

// Handle navigation
function handleNavigation(target) {
    switch(target) {
        case 'library':
            // Load library content
            alert('Kutubxona tez orada!');
            break;
        case 'tasks':
            // Show all tasks
            alert('Barcha tasklar tez orada!');
            break;
        case 'profile':
            // Show profile
            showProfile();
            break;
    }
}

// Show profile
function showProfile() {
    let isPremium = localStorage.getItem('isPremium') === 'true';
    let trialDays = localStorage.getItem('trialDays') || '3';
    
    let profileHTML = `
        <div class="content-card">
            <div class="card-header">
                <div class="card-icon">👤</div>
                <div class="card-title">Profil</div>
            </div>
            <div style="margin-bottom: 15px;">
                <div><strong>Ism:</strong> ${userName}</div>
                <div><strong>ID:</strong> ${userId}</div>
                <div><strong>Maqsad:</strong> ${localStorage.getItem('userGoal') || 'Kasbiy'}</div>
                <div><strong>Kunlik:</strong> ${localStorage.getItem('dailyHours') || '2'} soat</div>
            </div>
            ${isPremium ? 
                '<div style="color: #4CAF50; font-weight: 600;">💎 Premium aktiv</div>' :
                `<div style="color: #FF9800; font-weight: 600;">🎁 Trial: ${trialDays} kun qoldi</div>`
            }
            <button style="width: 100%; padding: 12px; margin-top: 15px; background: #2196F3; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer;" onclick="openPayment()">
                💎 Premium sotib olish
            </button>
        </div>
    `;
    
    document.getElementById('contentGrid').innerHTML = profileHTML;
}

// Open payment page
function openPayment() {
    tg.openLink('payment.html');
}

// Initialize app when page loads
document.addEventListener('DOMContentLoaded', initApp);