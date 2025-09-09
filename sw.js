// MemoWindow Service Worker
const CACHE_NAME = 'memowindow-v1.0.0';
const STATIC_CACHE = [
  '/',
  '/index.html',
  '/dist/bundle.js',
  '/manifest.json',
  '/images/logo.png',
  // Add other static assets as needed
];

const DYNAMIC_CACHE_SIZE = 50;

// Install event - cache static assets
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Caching static assets');
        return cache.addAll(STATIC_CACHE);
      })
      .then(() => {
        console.log('Service Worker: Installation complete');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('Service Worker: Installation failed', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              console.log('Service Worker: Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('Service Worker: Activation complete');
        return self.clients.claim();
      })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
  const { request } = event;
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip Chrome extension requests
  if (request.url.startsWith('chrome-extension://')) {
    return;
  }

  // Handle different types of requests
  if (request.url.includes('/api/') || request.url.includes('.php')) {
    // API requests - network first
    event.respondWith(networkFirst(request));
  } else if (request.destination === 'image') {
    // Images - cache first
    event.respondWith(cacheFirst(request));
  } else {
    // Static assets - stale while revalidate
    event.respondWith(staleWhileRevalidate(request));
  }
});

// Network first strategy (for API calls)
async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Service Worker: Network failed, trying cache');
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline page for navigation requests
    if (request.destination === 'document') {
      return caches.match('/index.html');
    }
    
    throw error;
  }
}

// Cache first strategy (for images)
async function cacheFirst(request) {
  const cachedResponse = await caches.match(request);
  
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      
      // Limit cache size
      const keys = await cache.keys();
      if (keys.length >= DYNAMIC_CACHE_SIZE) {
        await cache.delete(keys[0]);
      }
      
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Service Worker: Failed to fetch:', request.url);
    throw error;
  }
}

// Stale while revalidate strategy (for static assets)
async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE_NAME);
  const cachedResponse = await cache.match(request);
  
  const fetchPromise = fetch(request).then(networkResponse => {
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  }).catch(error => {
    console.log('Service Worker: Network fetch failed:', error);
  });
  
  return cachedResponse || fetchPromise;
}

// Handle background sync for offline functionality
self.addEventListener('sync', event => {
  console.log('Service Worker: Background sync triggered');
  
  if (event.tag === 'background-sync') {
    event.waitUntil(handleBackgroundSync());
  }
});

async function handleBackgroundSync() {
  // Handle any queued actions when back online
  console.log('Service Worker: Handling background sync');
  
  try {
    // Check if we're back online
    const response = await fetch('/ping.php');
    
    if (response.ok) {
      // Process any queued uploads or actions
      console.log('Service Worker: Back online, processing queued actions');
      
      // Notify clients that we're back online
      const clients = await self.clients.matchAll();
      clients.forEach(client => {
        client.postMessage({ type: 'BACK_ONLINE' });
      });
    }
  } catch (error) {
    console.log('Service Worker: Still offline');
  }
}

// Handle push notifications (if needed later)
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json();
    
    const options = {
      body: data.body,
      icon: '/images/icon-192.png',
      badge: '/images/icon-192.png',
      vibrate: [200, 100, 200],
      data: data.data,
      actions: data.actions || []
    };
    
    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  event.waitUntil(
    self.clients.openWindow(event.notification.data.url || '/')
  );
});

console.log('Service Worker: Loaded and ready');
