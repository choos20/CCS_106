class MindTwisterGame {
  constructor() {
    // DOM elements
    this.boardEl = document.getElementById('gameBoard');
    this.livesEl = document.getElementById('lives');
    this.matchesEl = document.getElementById('matches');
    this.movesEl = document.getElementById('moves');
    this.messageEl = document.getElementById('message');
    this.timerEl = document.getElementById('timer');
    this.bestTimeEl = document.getElementById('bestTime');
    this.levelSelect = document.getElementById('level');
    this.categoryContainer = document.getElementById('categoryCards');
    this.startBtn = document.getElementById('startGameBtn');
    this.restartBtn = document.getElementById('restartBtn');
    this.winActionBtn = document.getElementById('winActionBtn');
    this.hintBtn = document.getElementById('hintBtn');
    this.pauseBtn = document.getElementById('pauseBtn');
    this.settingsBtn = document.getElementById('settingsBtn');
    this.soundToggle = document.getElementById('soundToggle');
    this.musicToggle = document.getElementById('musicToggle');
    this.musicVolume = document.getElementById('musicVolume');

    // Audio elements
    this.bgMusic = document.getElementById('bgMusic');

    // Modals
    this.funFactModal = new bootstrap.Modal(document.getElementById('funFactModal'));
    this.settingsModal = new bootstrap.Modal(document.getElementById('settingsModal'));

    // Game state
    this.firstCard = null;
    this.secondCard = null;
    this.lockBoard = false;
    this.lives = 0;
    this.matches = 0;
    this.moves = 0;
    this.totalPairs = 0;
    this.currentLevel = null;
    this.currentCategory = null;
    this.gamePaused = false;
    this.timerInterval = null;
    this.timeElapsed = 0;
    this.freeLifeUsed = false;
    this.freeLifeClaimable = false;
    this.gameActive = false;
    this.cards = [];
    this.hintUsed = false;

    // Audio context for sound effects
    this.audioCtx = null;
    this.soundEnabled = true;

    // Data
    this.categories = {
      animals: [
'🐶','🐱','🦁','🐼','🐸','🦊',
'🐷','🐵','🦄','🐉','🦖','🦕',
'🐯','🐨','🐻','🐮','🐔','🦆',
'🦉','🐺','🦋','🐝','🐞','🦀',
'🐙','🐢','🦎','🐍','🦓','🦒',
'🐘','🦏','🦛','🐪','🐫','🦥'
],

fruits: [
'🍎','🍌','🍇','🍉','🍓','🍒',
'🥝','🍍','🍑','🍐','🍊','🍋',
'🥭','🍈','🍏','🍅','🍆','🥑',
'🌶️','🥕','🌽','🥔','🍠','🥦',
'🥬','🧄','🧅','🥜','🌰','🍄',
'🥥','🫐','🍞','🥐','🥖','🧀'
],

vehicles: [
'🚗','🚌','🏍️','🚁','🚢','🚂',
'🚚','🚲','🚀','🛸','🚜','🛵',
'🚓','🚑','🚒','🚐','🚕','🚖',
'🚔','🚍','🚘','🚖','🚡','🚠',
'🚃','🚋','🚞','🚄','✈️','🛶',
'⛵','🛥️','🚤','🛳️','🚁','🚁'
],

faces: [
'😀','😎','😢','😡','😱','🤓',
'🥳','🤯','😷','🥶','😂','😍',
'🤔','😴','😇','🤩','😤','😜',
'😅','😆','🙂','🙃','😬','🤗',
'😈','👿','🤠','🥺','🫠','🫡',
'🤤','😵','😵‍💫','🤢','🤮','🤧'
],

food: [
'🍔','🍕','🌭','🍟','🥪','🥗',
'🍩','🍪','🍿','🧁','🍦','🥨',
'🍰','🎂','🍫','🍬','🍭','🥞',
'🧇','🥓','🍳','🍗','🍖','🌮',
'🌯','🥙','🍜','🍝','🍣','🍱',
'🍛','🍚','🍤','🥟','🍢','🍡'
],

nature: [
'🌳','🌷','🌞','🌧️','🌊','🌵',
'🌸','🍂','🌴','🍁','⛰️','🌈',
'🌺','🌻','🌼','🌱','🌿','☘️',
'🍀','🌾','🌲','🌧','⛈️','🌩️',
'❄️','☃️','🌫️','🌬️','🔥','💧',
'🌙','⭐','🌍','🌎','🌏','🪨'
],

books: [
'📖','📕','📗','📘','📙','📚',
'📓','📃','📜','📰','📔','📒',
'📑','🔖','🏷️','📝','✏️','🖊️',
'🖋️','✒️','📏','📐','📎','🖇️',
'📌','📍','🗂️','📂','📁','🗃️',
'🗄️','🗑️','📋','📊','📈','📉'
],
      memes: [
        
        '../images/m1.jpg',
        '../images/m2.jpg',
        '../images/m3.jpg',
        '../images/m4.jpg',
        '../images/m5.jpg',
        '../images/m6.jpg',
        '../images/m7.jpg',
        '../images/m8.jpg',
        '../images/m9.jpg',
        '../images/m10.jpg',
        '../images/m11.jpg',
        '../images/m12.jpg',
        '../images/m13.jpg',
        '../images/m14.jpg',
        '../images/m15.jpg',
        '../images/m16.jpg',
        '../images/m17.jpg',
        '../images/m18.jpg',
        '../images/m19.jpg',
        '../images/m20.jpg',


      ],
      random: []
    };

    this.funFacts = {
animals: [
'Cats sleep 12-16 hrs.',
'Dogs have three eyelids.',
'Elephants can\'t jump.',
'A group of lions is called a pride.',
'Octopus have three hearts.',
'Giraffes have blue tongues.',
'Koalas sleep 20 hrs a day.',
'Slugs have four noses.',
'Dolphins have names for each other.',
'Sharks existed before trees.',
'Butterflies taste with feet.',
'Owls can rotate heads 270°.',
'Cows have best friends.',
'Frogs absorb water through skin.',
'Penguins propose with pebbles.'
],

fruits: [
'Apples float due to air.',
'Bananas are berries.',
'Watermelons are 92% water.',
'Pineapples take 2 yrs to grow.',
'Strawberries have seeds outside.',
'Lemons float but limes sink.',
'Tomatoes are fruits.',
'Coconuts are seeds.',
'Grapes explode in microwave plasma.',
'Peaches belong to rose family.',
'Kiwi originally called Chinese gooseberry.',
'Avocados are berries.',
'Figs contain tiny flowers.',
'Mangoes are most eaten fruit.',
'Pumpkins are fruits.'
],

vehicles: [
'First car in 1886.',
'Longest bridge 164 km.',
'Fastest car >300 mph.',
'Electric cars 19th century.',
'First airplane flight 1903.',
'Bullet trains exceed 300 km/h.',
'First traffic light 1868.',
'Cars have about 30k parts.',
'First motorcycle 1885.',
'Submarines travel underwater months.',
'Cruise ships hold 6000+ passengers.',
'Helicopters can hover in place.',
'First jet flight 1939.',
'Formula 1 cars corner >5g.',
'Space rockets reach 28,000 km/h.'
],

faces: [
'Smiling releases endorphins.',
'Humans have 40 facial muscles.',
'Blushing is unique.',
'Eyebrows prevent sweat.',
'Babies smile in sleep.',
'Laughter boosts immunity.',
'Yawning cools the brain.',
'Eye contact builds trust.',
'Blinking keeps eyes moist.',
'Dimples are muscle variations.',
'Fake smiles use fewer muscles.',
'Surprise raises eyebrows instantly.',
'People mirror others expressions.',
'Facial expressions are universal.',
'Smiling can improve mood.'
],

food: [
'Chocolate was currency.',
'Honey never spoils.',
'Potatoes grew in space.',
'Tomatoes are fruits.',
'Carrots were once purple.',
'Cheese is most stolen food.',
'Popcorn pops at 180°C.',
'Peanuts are legumes.',
'Rice feeds half the world.',
'Pizza originated in Naples.',
'Ice cream existed in ancient China.',
'Spicy food releases endorphins.',
'Bread is 14,000 years old.',
'Sushi originally preserved fish.',
'Ketchup was once medicine.'
],

nature: [
'Bamboo grows 35 in/day.',
'Tree oxygen for 4 people.',
'Lightning 8M/day.',
'Everest grows 4mm/yr.',
'Rain smells from soil bacteria.',
'Banana plants are giant herbs.',
'Antarctica is largest desert.',
'Sunlight takes 8 minutes to Earth.',
'Oceans cover 71% Earth.',
'Some trees live 5000 years.',
'Volcanoes create new land.',
'Coral reefs are living animals.',
'Clouds weigh tons.',
'Raindrops aren\'t tear shaped.',
'Moon causes ocean tides.'
],

books: [
'Longest novel 9M chars.',
'Reading reduces stress 68%.',
'Bookworm word origin.',
'First printed book Gutenberg.',
'Libraries existed 2600 years ago.',
'Oldest book printed 868 AD.',
'Dr. Seuss used 50 words in Green Eggs.',
'Shakespeare invented many words.',
'Bookmarks date to medieval times.',
'First comic book 1933.',
'E-books appeared in 1971.',
'Average novel 70k words.',
'World Book Day April 23.',
'Braille books help blind readers.',
'Libraries lend millions daily.'
],

memes: [
'Doge meme began 2010.',
'Rickrolling popular 2007.',
'Dancing Baby appeared 1996.',
'Grumpy Cat real name Tardar Sauce.',
'Pepe the Frog started as comic.',
'Success Kid photo 2007.',
'Keyboard Cat video 2007.',
'Nyan Cat video 2011.',
'Hide the Pain Harold stock photo.',
'Distracted Boyfriend 2015 photo.',
'Woman Yelling at Cat meme.',
'Roll Safe meme from BBC show.',
'Galaxy Brain meme about ideas.',
'This Is Fine dog comic.',
'Among Us memes exploded 2020.'
]
};

    // High scores (localStorage)
    this.highScores = JSON.parse(localStorage.getItem('mindTwisterHighScores')) || {};

    // Bind methods
    this.initEventListeners();
    this.createParticles(20); // optional floating dots
  }

  initEventListeners() {
    this.levelSelect.addEventListener('change', () => this.showCategoryCards());
    this.startBtn.addEventListener('click', () => this.startOrRestart());
    this.restartBtn.addEventListener('click', () => this.restartLevel());
    this.pauseBtn.addEventListener('click', () => this.togglePause());
    this.settingsBtn.addEventListener('click', () => this.settingsModal.show());
    this.hintBtn.addEventListener('click', () => this.hint());
    document.getElementById('closeFunFact').addEventListener('click', () => this.grantFreeLife());
    this.winActionBtn.addEventListener('click', () => this.handleWinAction());

    // Music toggles & volume
    this.musicToggle.addEventListener('change', () => this.toggleBackgroundMusic());
    this.musicVolume.addEventListener('input', (e) => {
      this.bgMusic.volume = e.target.value / 100;
    });
    this.soundToggle.addEventListener('change', (e) => {
      this.soundEnabled = e.target.checked;
    });

    // Resume audio context on first user interaction (for sound effects)
    document.addEventListener('click', () => this.resumeAudioContext(), { once: true });
  }

  // ---------- Particles (decorative) ----------
  createParticles(count) {
    for (let i = 0; i < count; i++) {
      const particle = document.createElement('div');
      particle.className = 'particle';
      particle.style.left = Math.random() * 100 + '%';
      particle.style.animationDelay = Math.random() * 15 + 's';
      particle.style.animationDuration = 10 + Math.random() * 20 + 's';
      document.body.appendChild(particle);
    }
  }

  // ---------- Audio Helpers ----------
  resumeAudioContext() {
    if (this.audioCtx && this.audioCtx.state === 'suspended') {
      this.audioCtx.resume();
    }
  }

  initAudioContext() {
    if (this.audioCtx) return;
    this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  }

  toggleBackgroundMusic() {
    if (this.musicToggle.checked) {
      this.bgMusic.play().catch(e => console.log('Music autoplay blocked'));
    } else {
      this.bgMusic.pause();
      this.bgMusic.currentTime = 0;
    }
  }

  startBackgroundMusic() {
    if (this.musicToggle.checked) {
      this.bgMusic.volume = this.musicVolume.value / 100;
      this.bgMusic.play().catch(() => {
        // Autoplay blocked – try on first click
        const playOnClick = () => {
          this.bgMusic.play();
          document.removeEventListener('click', playOnClick);
        };
        document.addEventListener('click', playOnClick);
      });
    }
  }

  stopBackgroundMusic() {
    this.bgMusic.pause();
    this.bgMusic.currentTime = 0;
  }

  playSound(type) {
    if (!this.soundEnabled) return;
    this.initAudioContext();
    if (this.audioCtx.state === 'suspended') {
      this.audioCtx.resume();
    }
    const osc = this.audioCtx.createOscillator();
    const gain = this.audioCtx.createGain();
    osc.connect(gain);
    gain.connect(this.audioCtx.destination);

    const now = this.audioCtx.currentTime;
    switch (type) {
      case 'flip':
        osc.frequency.value = 800;
        gain.gain.setValueAtTime(0.1, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
        break;
      case 'match':
        osc.frequency.value = 1200;
        gain.gain.setValueAtTime(0.2, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
        break;
      case 'wrong':
        osc.frequency.value = 300;
        gain.gain.setValueAtTime(0.2, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
        break;
      case 'win':
        this.simpleWinSound();
        return;
      case 'gameover':
        osc.frequency.value = 150;
        gain.gain.setValueAtTime(0.3, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.5);
        break;
      default: return;
    }
    osc.start(now);
    osc.stop(now + 0.2);
  }

  simpleWinSound() {
    if (!this.soundEnabled) return;
    this.initAudioContext();
    if (this.audioCtx.state === 'suspended') this.audioCtx.resume();
    for (let i = 0; i < 3; i++) {
      const osc = this.audioCtx.createOscillator();
      const gain = this.audioCtx.createGain();
      osc.connect(gain);
      gain.connect(this.audioCtx.destination);
      osc.frequency.value = 600 + i * 200;
      gain.gain.setValueAtTime(0.1, this.audioCtx.currentTime + i * 0.1);
      gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + i * 0.1 + 0.3);
      osc.start(this.audioCtx.currentTime + i * 0.1);
      osc.stop(this.audioCtx.currentTime + i * 0.1 + 0.3);
    }
  }

  // ---------- Category Display ----------
  showCategoryCards() {
    this.currentLevel = parseInt(this.levelSelect.value);
    if (!this.currentLevel) return;
    this.messageEl.textContent = `Level ${this.currentLevel} – pick a category`;
    this.categoryContainer.classList.remove('d-none');
    this.renderCategoryCards();
  }

  renderCategoryCards() {
    const html = Object.keys(this.categories).map(cat => `
      <div class="category-card" data-category="${cat}">
        ${this.getCategoryEmoji(cat)}<p>${cat}</p>
      </div>
    `).join('');
    this.categoryContainer.innerHTML = html;

    document.querySelectorAll('.category-card').forEach(card => {
      card.addEventListener('click', (e) => {
        if (!this.currentLevel) {
          this.messageEl.textContent = '⚠ Select a level first!';
          return;
        }
        this.selectCategory(card.dataset.category);
      });
    });
  }

  getCategoryEmoji(cat) {
    const map = {
      animals: '🐶', fruits: '🍎', vehicles: '🚗', faces: '😀',
      food: '🍔', nature: '🌿', books: '📖', memes: '😂', random: '🎲'
    };
    return map[cat] || '📦';
  }

  selectCategory(category) {
    this.currentCategory = category;
    document.querySelectorAll('.category-card').forEach(c => c.classList.remove('selected'));
    document.querySelector(`[data-category="${category}"]`).classList.add('selected');
    this.messageEl.textContent = `Category: ${category}. Click Start!`;
  }

  // ---------- Game Flow ----------
  startOrRestart() {
    if (!this.currentLevel || !this.currentCategory) {
      this.messageEl.textContent = '⚠ Select level and category first!';
      return;
    }
    this.startGame();
  }

  startGame() {
    // Reset state
    this.gameActive = true;
    this.gamePaused = false;
    this.pauseBtn.textContent = '⏸️ Pause';
    this.firstCard = this.secondCard = null;
    this.lockBoard = false;
    this.matches = 0;
    this.moves = 0;
    this.timeElapsed = 0;
    this.freeLifeUsed = false;
    this.freeLifeClaimable = false;
    this.hintUsed = false;
    this.hintBtn.disabled = false;
    this.hintBtn.classList.remove('disappear');
    this.updateStats();

    // Show/hide hint button based on level
    if (this.currentLevel >= 3) {
      this.hintBtn.classList.remove('d-none');
    } else {
      this.hintBtn.classList.add('d-none');
    }

    // Set lives
    const livesMap = [5, 7, 10, 15, 20];
    this.lives = livesMap[this.currentLevel - 1];
    this.livesEl.textContent = this.lives;

    this.winActionBtn.classList.add('d-none');
    this.boardEl.classList.remove('d-none');

    clearInterval(this.timerInterval);
    this.timerInterval = setInterval(() => this.tick(), 1000);

    // Prepare board
    const gridSize = this.currentLevel + 1;
    const totalCards = gridSize * gridSize;
    this.totalPairs = Math.floor(totalCards / 2);
    this.boardEl.style.gridTemplateColumns = `repeat(${gridSize}, minmax(70px, 1fr))`;

    // Emoji scaling based on level
    const baseSize = Math.max(1.6, 3 - this.currentLevel * 0.3);
    this.boardEl.style.setProperty('--card-font-size', `${baseSize}rem`);

    let itemPool = this.currentCategory === 'random'
      ? Object.values(this.categories).flat().filter(v => typeof v === 'string')
      : [...this.categories[this.currentCategory]];

    const shuffled = this.shuffle(itemPool);
    const selected = shuffled.slice(0, this.totalPairs);
    let cardItems = [...selected, ...selected];
    if (totalCards % 2 !== 0) cardItems.push('⭐');
    cardItems = this.shuffle(cardItems);

    this.cards = cardItems.map(item => this.createCard(item));
    this.boardEl.innerHTML = '';
    this.cards.forEach(card => this.boardEl.appendChild(card));

    if (this.currentLevel >= 3) {
      this.lockBoard = true;
      this.messageEl.textContent = '✨ Memorize!';
      setTimeout(() => this.cards.forEach(c => c.classList.add('flipped')), 200);
      setTimeout(() => {
        this.cards.forEach(c => c.classList.remove('flipped'));
        this.messageEl.textContent = '';
        this.lockBoard = false;
      }, 2200);
    }

    this.updateBestTimeDisplay();
    this.startBackgroundMusic();
  }

  createCard(item) {
    const card = document.createElement('div');
    card.className = 'card-tile card-enter';
    card.style.setProperty('--dx', `${(Math.random() - 0.5) * 100}px`);
    card.style.setProperty('--dy', `${(Math.random() - 0.5) * 100}px`);
    card.dataset.item = item;

    const span = document.createElement('span');
    if (typeof item === 'string' && item.match(/\.(png|jpg|jpeg|gif)$/i)) {
      const img = document.createElement('img');
      img.src = item;
      span.appendChild(img);
    } else {
      span.textContent = item;
    }
    card.appendChild(span);
    card.addEventListener('click', () => this.flipCard(card));
    return card;
  }

  flipCard(card) {
    if (this.lives <= 0) {
      this.messageEl.textContent = '💀 No lives left. Restart to play.';
      return;
    }
    if (this.lockBoard || this.gamePaused || !this.gameActive ||
        card === this.firstCard || card.classList.contains('flipped')) return;

    card.classList.add('flipped');
    this.playSound('flip');

    if (!this.firstCard) {
      this.firstCard = card;
      return;
    }

    this.secondCard = card;
    this.moves++;
    this.movesEl.textContent = this.moves;

    this.lockBoard = true;
    setTimeout(() => this.checkMatch(), 500); //Dara hahahah
  }

  checkMatch() {
    const match = this.firstCard.dataset.item === this.secondCard.dataset.item;

    if (match) {
      this.matches++;
      this.matchesEl.textContent = this.matches;
      this.playSound('match');
      this.firstCard.classList.add('correct-match');
      this.secondCard.classList.add('correct-match');
      setTimeout(() => {
        this.firstCard.classList.remove('correct-match');
        this.secondCard.classList.remove('correct-match');
      }, 500);

      if (this.matches === this.totalPairs) this.win();
      this.resetTurn();
    } else {
      this.lives--;
      this.livesEl.textContent = this.lives;
      this.playSound('wrong');

      if (this.lives === 0) {
        if (!this.freeLifeUsed) {
          this.gamePaused = true;
          this.showFunFacts();
          return;
        } else {
          this.gameOver();
        }
      }

      this.firstCard.classList.add('wrong-match');
      this.secondCard.classList.add('wrong-match');
      setTimeout(() => {
        this.firstCard.classList.remove('wrong-match');
        this.secondCard.classList.remove('wrong-match');
        this.firstCard.classList.remove('flipped');
        this.secondCard.classList.remove('flipped');
        this.resetTurn();
      }, );
    }
  }

  resetTurn() {
    this.firstCard = this.secondCard = null;
    this.lockBoard = false;
  }

  win() {
    clearInterval(this.timerInterval);
    this.gameActive = false;
    this.gamePaused = true;
    this.playSound('win');
    this.confetti();

    const timeStr = this.formatTime(this.timeElapsed);
    this.messageEl.textContent = `🎉 Level cleared in ${timeStr}!`;

    const key = `${this.currentLevel}-${this.currentCategory}`;
    if (!this.highScores[key] || this.timeElapsed < this.highScores[key]) {
      this.highScores[key] = this.timeElapsed;
      localStorage.setItem('mindTwisterHighScores', JSON.stringify(this.highScores));
      this.messageEl.textContent += ' New record!';
    }
    this.updateBestTimeDisplay();

    // Record the game session
    this.recordGame();

    if (this.currentLevel < 5) {
      this.winActionBtn.textContent = '▶ Next Level';
      this.winActionBtn.dataset.action = 'next';
    } else {
      this.winActionBtn.textContent = '⟲ Play Again';
      this.winActionBtn.dataset.action = 'restart';
    }
    this.winActionBtn.classList.remove('d-none');
  }

  gameOver() {
    clearInterval(this.timerInterval);
    this.gameActive = false;
    this.gamePaused = true;
    this.playSound('gameover');
    this.messageEl.textContent = '💀 Game Over... Restart?';

    // Record the game session (even if lost)
    this.recordGame();

    this.winActionBtn.textContent = '⟲ Restart Level';
    this.winActionBtn.dataset.action = 'restart';
    this.winActionBtn.classList.remove('d-none');
  }

  recordGame() {
    // Calculate score: lower moves is better, but we store it as a positive value.
    // (The admin dashboard now treats the score as a positive metric.)
    const score = this.moves;
    const formData = new FormData();
    formData.append('score', score);
    formData.append('moves', this.moves);
    formData.append('time_taken', this.timeElapsed);

    fetch('../ALL_PHP/record_game.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log('Game recorded successfully');
      } else {
        console.error('Failed to record game:', data.message);
      }
    })
    .catch(error => {
      console.error('Error recording game:', error);
    });
  }

  handleWinAction() {
    if (this.winActionBtn.dataset.action === 'next') {
      this.currentLevel++;
      this.levelSelect.value = this.currentLevel;
      this.startGame();
    } else {
      this.restartLevel();
    }
  }

  restartLevel() {
    if (this.currentLevel && this.currentCategory) {
      this.startGame();
    } else {
      this.messageEl.textContent = 'Select level and category first.';
    }
  }

  showFunFacts() {
    const facts = this.funFacts[this.currentCategory] || ['Keep trying!'];
    const randomFacts = this.shuffle(facts).slice(0, 3);
    const list = document.getElementById('funFactsList');
    list.innerHTML = randomFacts.map(f => `<li>📌 ${f}</li>`).join('');

    this.freeLifeClaimable = false;
    const closeBtn = document.getElementById('closeFunFact');
    closeBtn.disabled = true;
    let timeLeft = 15;
    const timerSpan = document.getElementById('freeLifeTimer');
    timerSpan.textContent = timeLeft;

    const countdown = setInterval(() => {
      timeLeft--;
      timerSpan.textContent = timeLeft;
      if (timeLeft <= 0) {
        clearInterval(countdown);
        this.freeLifeClaimable = true;
        closeBtn.disabled = false;
      }
    }, 1000);

    const modalEl = document.getElementById('funFactModal');
    const hideHandler = () => {
      clearInterval(countdown);
      closeBtn.disabled = true;
      this.freeLifeClaimable = false;
      modalEl.removeEventListener('hidden.bs.modal', hideHandler);
    };
    modalEl.addEventListener('hidden.bs.modal', hideHandler);

    this.funFactModal.show();
  }

  grantFreeLife() {
    if (!this.freeLifeClaimable) return;
    this.funFactModal.hide();
    this.freeLifeUsed = true;
    this.lives = 1;
    this.livesEl.textContent = this.lives;
    this.messageEl.textContent = '❤️ Free life granted!';
    this.gamePaused = false;
    this.lockBoard = false;
  }

  hint() {
    if (this.currentLevel < 3) {
      this.messageEl.textContent = 'Hint only available from level 3!';
      return;
    }
    if (this.hintUsed) {
      this.messageEl.textContent = 'You already used your hint this game.';
      return;
    }
    if (this.lockBoard || !this.gameActive || this.gamePaused) return;

    this.hintUsed = true;
    this.hintBtn.disabled = true;
    this.hintBtn.classList.add('disappear');
    setTimeout(() => {
      this.hintBtn.classList.add('d-none');
    }, 500);
    this.lockBoard = true;
    this.cards.forEach(c => c.classList.add('flipped'));
    setTimeout(() => {
      this.cards.forEach(c => {
        if (c !== this.firstCard && c !== this.secondCard && !c.classList.contains('matched')) {
          c.classList.remove('flipped');
        }
      });
      this.lockBoard = false;
    }, 3000);
  }

  tick() {
    if (!this.gamePaused && this.gameActive) {
      this.timeElapsed++;
      this.timerEl.textContent = this.formatTime(this.timeElapsed);
    }
  }

  formatTime(sec) {
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
  }

  updateStats() {
    this.livesEl.textContent = this.lives;
    this.matchesEl.textContent = this.matches;
    this.movesEl.textContent = this.moves;
  }

  updateBestTimeDisplay() {
    const key = `${this.currentLevel}-${this.currentCategory}`;
    const best = this.highScores[key];
    this.bestTimeEl.textContent = best ? this.formatTime(best) : '-';
  }

  shuffle(array) {
    for (let i = array.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
  }

  togglePause() {
    if (!this.gameActive) return;
    this.gamePaused = !this.gamePaused;
    this.pauseBtn.textContent = this.gamePaused ? '▶ Resume' : '⏸️ Pause';
    this.messageEl.textContent = this.gamePaused ? '⏸ Game paused' : '';
  }

  confetti() {
    confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 } });
  }
}

// Start the game
document.addEventListener('DOMContentLoaded', () => {
  window.game = new MindTwisterGame();
});

// At the start of game, get challenge id from URL
const urlParams = new URLSearchParams(window.location.search);
const challengeId = urlParams.get('challenge');

// When the game is won, after confetti, send progress
if (challengeId) {
    fetch('update_challenge.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            challenge_id: challengeId,
            progress: matches // or whatever variable holds matches count
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              alert('Challenge progress updated!');
          }
      });
}