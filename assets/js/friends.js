document.addEventListener('DOMContentLoaded', () => {
  
  // Create and append modal HTML once on page load
  const modalHtml = `
    <div id="removeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
        <h3 class="text-lg font-semibold mb-4">Confirm Remove Friend</h3>
        <p id="modalFriendName" class="mb-6 text-gray-700"></p>
        <div class="flex justify-end space-x-4">
          <button id="modalCancelBtn" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Cancel</button>
          <button id="modalRemoveBtn" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">Remove</button>
        </div>
      </div>
    </div>
  `;
  document.body.insertAdjacentHTML('beforeend', modalHtml);

  let selectedFriendId = null;

  // Modal elements
  const modal = document.getElementById('removeModal');
  const modalFriendName = document.getElementById('modalFriendName');
  const cancelBtn = document.getElementById('modalCancelBtn');
  const removeBtn = document.getElementById('modalRemoveBtn');

  // Cancel button closes modal
  cancelBtn.addEventListener('click', () => {
    selectedFriendId = null;
    modal.classList.add('hidden');
  });

  // Remove button triggers removeFriend and closes modal
  removeBtn.addEventListener('click', () => {
    if (selectedFriendId !== null) {
      removeFriend(selectedFriendId);
    }
    selectedFriendId = null;
    modal.classList.add('hidden');
  });

  // Replace confirmRemove function to open modal instead of alert
  window.confirmRemove = function(friendId, name) {
    selectedFriendId = friendId;
    modalFriendName.textContent = `Are you sure you want to remove ${name} from your friend list?`;
    modal.classList.remove('hidden');
  };

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

      // Sort: pending → accepted → none
      users.sort((a, b) => {
        const order = { pending: 0, accepted: 1, none: 2 };
        return order[a.relation_status] - order[b.relation_status];
      });

      users.forEach((user, index) => {
        const profilePic = user.profile_pic ? `../${user.profile_pic}` : '../assets/img/default-profile.png';
        const isFriend = user.relation_status === 'accepted';
        const isPending = user.relation_status === 'pending';

        let statusLabel = '';
        let buttonHTML = '';

        if (isFriend) {
          statusLabel = '<span class="text-green-600 text-sm font-medium">✅ Already Friends</span>';
        } else if (isPending) {
          statusLabel = '<span class="text-yellow-600 text-sm font-medium">⏳ Request Pending</span>';
        }

        if (!isFriend && !isPending) {
          buttonHTML = `
            <button onclick="sendRequest(${user.id})"
              class="bg-blue-600 text-white px-4 py-1.5 rounded-full hover:bg-blue-700 transition">
              Add
            </button>
          `;
        }

        const card = document.createElement('div');
        card.className = `
          flex items-center justify-between p-4 bg-white rounded-xl shadow
          transition-all duration-300 ease-out transform opacity-0 translate-y-2
          ${isFriend || isPending ? 'opacity-60 pointer-events-none' : ''}
        `;
        card.innerHTML = `
          <div class="flex items-center gap-4">
            <img src="${profilePic}" alt="Profile"
                 class="w-12 h-12 rounded-full object-cover border-2 border-blue-500" />
            <div>
              <p class="text-gray-800 font-semibold">${user.name}</p>
              <p class="text-sm text-gray-500">${user.email}</p>
              ${statusLabel}
            </div>
          </div>
          ${buttonHTML}
        `;

        resultsDiv.appendChild(card);

        // Fade/slide in with slight delay for each card
        setTimeout(() => {
          card.classList.remove('opacity-0', 'translate-y-2');
        }, index * 60); // stagger animation
      });

    } catch (err) {
      resultsDiv.innerHTML = '<p class="text-red-500">Error loading users.</p>';
    }
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
    const sentList = document.getElementById('sentRequests');
    friendList.innerHTML = '';
    pendingList.innerHTML = '';
    sentList.innerHTML = '';

    // Accepted Friends
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
        <div class="flex flex-wrap gap-3 mt-auto">
          <a href="view_friend.php?friend_id=${friend.id}"
            class="bg-indigo-600 text-white text-sm px-4 py-1.5 rounded-full hover:bg-indigo-700 transition">
            View
          </a>
          <button onclick="confirmRemove(${friend.id}, '${friend.name}')"
            class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 px-4 py-1.5 rounded-full text-sm font-medium transition">
            Remove
          </button>
        </div>
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

    // Sent Requests
    data.sent.forEach(req => {
      const profilePic = req.profile_pic ? `../${req.profile_pic}` : '../assets/img/default-profile.png';

      const card = document.createElement('div');
      card.className = 'bg-white p-4 rounded-xl border shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:shadow-md transition-shadow duration-300';

      card.innerHTML = `
        <div class="flex items-center gap-4">
          <img src="${profilePic}" class="w-14 h-14 rounded-full object-cover border-2 border-gray-300" alt="Sent">
          <div>
            <p class="font-semibold text-gray-800">${req.name}</p>
            <p class="text-sm text-gray-500">${req.email ?? '<span class="italic text-gray-400">No email</span>'}</p>
          </div>
        </div>
        <button onclick="cancelRequest(${req.id})"
          class="bg-red-100 text-red-600 hover:bg-red-200 border border-red-300 px-4 py-1.5 rounded-full text-sm font-medium transition">
          Cancel
        </button>
      `;

      sentList.appendChild(card);
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

  async function cancelRequest(friendId) {
    // Cancel a sent friend request (removes the pending row)
    await fetch('remove_friend.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ friend_id: friendId })
    });
    loadFriends();
  }

  loadFriends();

});