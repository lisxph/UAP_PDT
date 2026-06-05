lucide.createIcons();

// LOGOUT CONFIRMATION MODAL

const logoutModal = document.getElementById('logoutModal');
const logoutLinks = document.querySelectorAll('[data-logout-link]');
const logoutCancelButtons = document.querySelectorAll('[data-logout-cancel]');

function openLogoutModal(event) {
  event.preventDefault();
  logoutModal?.classList.add('is-open');
  logoutModal?.setAttribute('aria-hidden', 'false');
}

function closeLogoutModal() {
  logoutModal?.classList.remove('is-open');
  logoutModal?.setAttribute('aria-hidden', 'true');
}

logoutLinks.forEach((link) => {
  link.addEventListener('click', openLogoutModal);
});

logoutCancelButtons.forEach((button) => {
  button.addEventListener('click', closeLogoutModal);
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') closeLogoutModal();
});

// NAVBAR SCROLL EFFECT

const navbar = document.querySelector('.navbar');

window.addEventListener('scroll', () => {

  if(window.scrollY > 30){

    navbar.style.background =
      'rgba(3, 10, 7, .82)';

    navbar.style.backdropFilter =
      'blur(20px)';

  }

  else {

    navbar.style.background =
      'rgba(3, 10, 7, .62)';

  }

});

// (rest omitted for brevity)
// ================================
// LOGIN - REGISTER SWITCH
// ================================

const showRegister = document.getElementById('showRegister');
const showLogin = document.getElementById('showLogin');
const showLoginText = document.getElementById('showLoginText');

const loginPanel = document.getElementById('loginPanel');
const registerPanel = document.getElementById('registerPanel');

if (showRegister && loginPanel && registerPanel) {

  showRegister.addEventListener('click', function(e) {

    e.preventDefault();

    // remove hidden class to override CSS that uses !important
    loginPanel.classList.add('auth-card-hidden');
    registerPanel.classList.remove('auth-card-hidden');

  });

}

if (showLogin && loginPanel && registerPanel) {

  showLogin.addEventListener('click', function(e) {

    e.preventDefault();

    registerPanel.classList.add('auth-card-hidden');
    loginPanel.classList.remove('auth-card-hidden');

  });

}
const INDONESIAN_MONTHS = {
  Jan: 0, Feb: 1, Mar: 2, Apr: 3, Mei: 4, Jun: 5,
  Jul: 6, Agu: 7, Sep: 8, Okt: 9, Nov: 10, Des: 11
};

function parseIndoDate(dateStr) {
  if (!dateStr) return null;
  const parts = dateStr.trim().split(' ');
  if (parts.length < 3) return null;
  const day = parseInt(parts[0], 10);
  const month = INDONESIAN_MONTHS[parts[1]];
  const year = parseInt(parts[2], 10);
  if (Number.isNaN(day) || month === undefined || Number.isNaN(year)) return null;
  return new Date(year, month, day);
}

function getCardStartDate(card) {
  const dateText = card.querySelector('.dest-date')?.textContent.trim();
  if (!dateText || dateText === '-') return null;
  const rangeParts = dateText.split('-').map(p => p.trim());
  return parseIndoDate(rangeParts[0] ?? dateText);
}

function parsePriceText(priceText) {
  if (!priceText) return 0;
  const cleaned = priceText.replace(/Rp\s*/gi, '').trim();
  if (/jt/i.test(cleaned)) {
    const number = parseFloat(cleaned.replace(/jt/i, '').replace(/\./g, '').trim()) || 0;
    return number * 1000000;
  }
  if (/rb/i.test(cleaned)) {
    const number = parseFloat(cleaned.replace(/rb/i, '').replace(/\./g, '').trim()) || 0;
    return number * 1000;
  }
  const digits = cleaned.replace(/[^\d]/g, '');
  return parseInt(digits, 10) || 0;
}

function getCardRating(card) {
  const badge = card.querySelector('.dest-badge')?.textContent;
  if (!badge) return 0;
  const value = badge.replace(/[^0-9.,]/g, '').replace(',', '.').trim();
  return parseFloat(value) || 0;
}

function sortDestinationCards(sortBy = 'default') {
  const destGrid = document.querySelector('.dest-grid');
  if (!destGrid) return;
  const cards = Array.from(destGrid.querySelectorAll('.dest-card'));

  cards.sort((a, b) => {
    if (sortBy === 'price_asc' || sortBy === 'price_desc') {
      const aPrice = parsePriceText(a.querySelector('.price')?.textContent || '0');
      const bPrice = parsePriceText(b.querySelector('.price')?.textContent || '0');
      return sortBy === 'price_asc' ? aPrice - bPrice : bPrice - aPrice;
    }

    if (sortBy === 'rating_desc') {
      const aRating = getCardRating(a);
      const bRating = getCardRating(b);
      return bRating - aRating;
    }

    if (sortBy === 'date_asc') {
      const aDate = getCardStartDate(a);
      const bDate = getCardStartDate(b);
      if (aDate === null && bDate === null) return 0;
      if (aDate === null) return 1;
      if (bDate === null) return -1;
      return aDate - bDate;
    }

    return 0;
  });

  cards.forEach((card) => destGrid.appendChild(card));
}

function sortDestinationCards(cards) {
  cards.sort((a, b) => {
    const aRating = getCardRating(a);
    const bRating = getCardRating(b);
    if (bRating !== aRating) {
      return bRating - aRating;
    }

    const aPrice = parsePriceText(a.querySelector('.price')?.textContent || '0');
    const bPrice = parsePriceText(b.querySelector('.price')?.textContent || '0');
    return aPrice - bPrice;
  });

  const destGrid = document.querySelector('.dest-grid');
  if (!destGrid) return;
  cards.forEach((card) => destGrid.appendChild(card));
}

function sortVisibleDestinationCards() {
  const destGrid = document.querySelector('.dest-grid');
  if (!destGrid) return;
  const cards = Array.from(destGrid.querySelectorAll('.dest-card'))
    .filter((card) => card.style.display !== 'none');
  sortDestinationCards(cards);
}

if (showLoginText && loginPanel && registerPanel) {

  showLoginText.addEventListener('click', function(e) {

    e.preventDefault();

    registerPanel.classList.add('auth-card-hidden');
    loginPanel.classList.remove('auth-card-hidden');

  });

}
