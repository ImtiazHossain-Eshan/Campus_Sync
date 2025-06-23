document.addEventListener('DOMContentLoaded', () => {
  
  loadFriends();

  const searchInput = document.getElementById('searchUser');
  searchInput.addEventListener('input', async (e) => {
    const query = e.target.value.trim();
    const resultsDiv = document.getElementById('searchResults');
    resultsDiv.innerHTML = '';

    if (query.length < 2) return;

    try {
      const res = await fetch('fetch_users.php?query=' + encodeURIComponent(query));
      const users = await res.json();

      if (users.length === 0) {
        resultsDiv.innerHTML = '<p class="text-gray-500">No users found.</p>';
        return;
      }

      users.forEach(user => {
        const profilePic = user.profile_pic ? `../${user.profile_pic}` : '../assets/img/default-profile.png';
        const card = `
          <div class="flex items-center justify-between p-4 bg-white rounded-xl shadow">
            <div class="flex items-center gap-4">
              <img src="${profilePic}" alt="Profile" class="w-12 h-12 rounded-full object-cover border-2 border-blue-500" />
              <div>
                <p class="text-gray-800 font-semibold">${user.name}</p>
                <p class="text-sm text-gray-500">${user.email}</p>
              </div>
            </div>
            <button onclick="sendRequest(${user.id})"
              class="bg-blue-600 text-white px-4 py-1.5 rounded-full hover:bg-blue-700 transition">Add</button>
          </div>
        `;
        resultsDiv.innerHTML += card;
      });
    } catch (err) {
      resultsDiv.innerHTML = '<p class="text-red-500">Error loading users.</p>';
    }
  });
});

async function sendRequest(friendId) {
  await fetch('send_friend_request.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ friend_id: friendId })
  });
  alert("Friend request sent!");
}

async function loadFriends() {
  const res = await fetch('fetch_friends.php');
  const data = await res.json();

  const friendList = document.getElementById('friendList');
  const pendingList = document.getElementById('pendingRequests');
  friendList.innerHTML = '';
  pendingList.innerHTML = '';

  // Accepted Friends - FIXED CARD LAYOUT
  data.accepted.forEach(friend => {
    const profilePic = friend.profile_pic ? `../${friend.profile_pic}` : '../assets/img/default-profile.png';

    const card = document.createElement('div');
    card.className = `
      bg-white p-4 rounded-xl border shadow-sm
      hover:shadow-md transition-shadow duration-300
      flex flex-col h-full
    `;

    card.innerHTML = `
      <div class="flex items-center gap-4 mb-4">
        <img src="${profilePic}" class="w-16 h-16 rounded-full object-cover border-2 border-blue-500 flex-shrink-0" alt="Friend">
        <div class="min-w-0">
          <p class="font-semibold text-gray-800 truncate">${friend.name}</p>
          <p class="text-sm text-gray-500 truncate">${friend.email ?? '<span class="italic text-gray-400">No email</span>'}</p>
        </div>
      </div>
      <button onclick="confirmRemove(${friend.id}, '${friend.name}')" 
        class="mt-auto bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 px-4 py-1.5 rounded-full text-sm font-medium transition">
        Remove
      </button>
    `;

    friendList.appendChild(card);
  });

  // Pending Requests
  data.pending.forEach(req => {
    const profilePic = req.profile_pic ? `../${req.profile_pic}` : '../assets/img/default-profile.png';

    const card = document.createElement('div');
    card.className = 'bg-white p-4 rounded-xl border shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:shadow-md transition-shadow duration-300';

    card.innerHTML = `
      <div class="flex items-center gap-4">
        <img src="${profilePic}" class="w-14 h-14 rounded-full object-cover border-2 border-yellow-400" alt="Pending">
        <div>
          <p class="font-semibold text-gray-800">${req.name}</p>
          <p class="text-sm text-gray-500">${req.email ?? '<span class="italic text-gray-400">No email</span>'}</p>
        </div>
      </div>
      <button onclick="acceptRequest(${req.id})"
        class="bg-green-500 text-white px-4 py-1.5 rounded-full hover:bg-green-600 transition text-sm font-medium">
        Accept
      </button>
    `;

    pendingList.appendChild(card);
  });
}



async function acceptRequest(friendId) {
  await fetch('accept_friend_request.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ friend_id: friendId })
  });
  loadFriends();
}

async function removeFriend(friendId) {
  await fetch('remove_friend.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ friend_id: friendId })
  });
  loadFriends();
}

function confirmRemove(friendId, name) {
  if (confirm(`Are you sure you want to remove ${name} from your friend list?`)) {
    removeFriend(friendId);
  }
}