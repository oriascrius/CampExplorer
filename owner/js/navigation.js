class Navigation {
  constructor() {
    this.baseUrl = '/CampExplorer/owner/';
    this.initializeEventListeners();
    this.updateSidebarActive();
  }

  initializeEventListeners() {
    document.querySelectorAll('.nav-link.nav-async-link').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const page = link.getAttribute('data-page');
        if (page) {
          this.loadContent(page);
          this.updateUrl(page);
          this.updateSidebarActive(page);
        }
      });
    });
  }

  updateUrl(page) {
    window.history.pushState({}, '', `${this.baseUrl}index.php?page=${page}`);
  }

  updateSidebarActive(page = null) {
    const currentPage = page || new URLSearchParams(window.location.search).get('page') || 'dashboard';
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.toggle('active', link.getAttribute('data-page') === currentPage);
    });
  }

  loadContent(page) {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;

    mainContent.style.opacity = '0.5';
    
    fetch(`${this.baseUrl}ajax_load_page.php?page=${page}`)
      .then(response => response.ok ? response.text() : Promise.reject('HTTP error'))
      .then(html => {
        mainContent.innerHTML = html;
        this.updateSidebarActive(page);
        mainContent.style.opacity = '1';
      })
      .catch(error => {
        console.error('載入頁面錯誤:', error);
        mainContent.style.opacity = '1';
      });
  }
}
