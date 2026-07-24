// Redirect to login
document.body.addEventListener("doRedirect", function(evt){
    window.location.href = evt.detail;
});

// // Listen for background updates from Electron
// if (window.electronAPI) {
    
//     // Debug Notifications
//     // Example 1: Directing user to a specific route on your PHP server
//     window.electronAPI.sendNotification(
//       'New Message Received', 
//       'Click to view your inbox.', 
//       'http://localhost:8000/monitoring'
//     );

//     // Example 2: Navigating to a settings page
//     window.electronAPI.sendNotification(
//       'Update Available', 
//       'Click here to configure settings.', 
//       'http://localhost:8000/settings'
//     );

//     // Listen for navigation requests from notification clicks
//     window.electronAPI.onNavigate((url) => {
//       console.log('Notification clicked! Navigating with HTMX to:', url);

//       // HTMX smooth content swap without a full browser reload
//       if (window.htmx) {
//         htmx.ajax('GET', url, { target: '#main-content', swap: 'innerHTML' });
//       } else {
//         // Fallback if HTMX isn't initialized yet
//         window.location.href = url;
//       }
//     });
//   }