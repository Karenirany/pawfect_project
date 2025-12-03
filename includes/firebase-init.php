<?php
// This file will be included in pages that need Firebase
?>
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-firestore-compat.js"></script>

<script>
  // Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyASCrVkQ_P7JLu79vXt9rTFYCpiu4cGRhU",
  authDomain: "pawfect-b4bc1.firebaseapp.com",
  projectId: "pawfect-b4bc1",
  storageBucket: "pawfect-b4bc1.firebasestorage.app",
  messagingSenderId: "383970177268",
  appId: "1:383970177268:web:a28df5b8f66d11d778eea1",
  measurementId: "G-6LMKZ94M0H"
};

  // Initialize Firebase
  firebase.initializeApp(firebaseConfig);
  
  // Initialize services
  const auth = firebase.auth();
  const db = firebase.firestore();
  
const analytics = getAnalytics(app);
</script>