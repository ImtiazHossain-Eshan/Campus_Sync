// assets/js/submit_routine.js

document.addEventListener('DOMContentLoaded', function() {
  const courseInput = document.getElementById('courseInput');
  const dataList = document.getElementById('courseSections');
  const courseNameField = document.getElementById('courseNameField');
  const facultyField = document.getElementById('facultyField');
  const slotSummary = document.getElementById('slotSummary');
  const hiddenSlotsContainer = document.getElementById('hiddenSlotsContainer');

  // Adjust these URLs according to your setup
  const fetchSectionsUrl = '/Campus_Sync/routines/fetch_course_sections.php';
  const fetchDetailsUrl = '/Campus_Sync/routines/fetch_course_details.php';

  // Load section codes
  async function loadSections() {
    try {
      const res = await fetch(fetchSectionsUrl);
      const json = await res.json();
      if (json.success) {
        dataList.innerHTML = '';
        json.sections.forEach(item => {
          const opt = document.createElement('option');
          opt.value = item.section_code;
          dataList.appendChild(opt);
        });
      } else {
        console.error('Failed to load sections:', json.error);
      }
    } catch (err) {
      console.error('Error fetching sections:', err);
    }
  }

  // Helper: clear previous slot summary and hidden inputs
  function clearSlots() {
    slotSummary.innerHTML = '<em>Select a section code to see slots</em>';
    hiddenSlotsContainer.innerHTML = '';
    courseNameField.value = '';
    facultyField.value = '';
  }

  // When a section code is selected or typed
  async function onSectionChange() {
    const sectionCode = courseInput.value.trim();
    clearSlots();
    if (!sectionCode) return;

    try {
      const res = await fetch(`${fetchDetailsUrl}?section_code=${encodeURIComponent(sectionCode)}`);
      const json = await res.json();
      if (!json.success) {
        slotSummary.textContent = 'No details found for this section code.';
        return;
      }
      const data = json.data;
      courseNameField.value = data.course_name || '';
      facultyField.value = data.faculty_initials || '';
      const slots = data.slots || [];
      if (slots.length === 0) {
        slotSummary.textContent = 'No schedule slots available.';
        return;
      }
      // Build summary text, e.g. "Sunday 15:30–16:50 (Room 09G-31T), Tuesday 15:30–16:50 ..."
      const summaryParts = [];
      slots.forEach((slot, idx) => {
        let part = '';
        if (slot.day) {
          part += slot.day;
        }
        if (slot.start_time && slot.end_time) {
          const st = slot.start_time.slice(0,5);
          const et = slot.end_time.slice(0,5);
          part += (part ? ' ' : '') + `${st}–${et}`;
        }
        if (slot.room) {
          part += ` (Room ${slot.room})`;
        }
        summaryParts.push(part);
      });
      slotSummary.textContent = summaryParts.join('; ');

      // Generate hidden inputs for each slot
      // Name them as slots[0][day], slots[0][start_time], slots[0][end_time], slots[0][room], slots[1][...], etc.
      hiddenSlotsContainer.innerHTML = ''; // clear
      slots.forEach((slot, idx) => {
        // Create hidden inputs only if required fields exist
        if (!slot.day || !slot.start_time || !slot.end_time) {
          return; // skip invalid slot
        }
        // day
        const inpDay = document.createElement('input');
        inpDay.type = 'hidden';
        inpDay.name = `slots[${idx}][day]`;
        inpDay.value = slot.day;
        hiddenSlotsContainer.appendChild(inpDay);
        // start_time
        const inpSt = document.createElement('input');
        inpSt.type = 'hidden';
        inpSt.name = `slots[${idx}][start_time]`;
        inpSt.value = slot.start_time;
        hiddenSlotsContainer.appendChild(inpSt);
        // end_time
        const inpEt = document.createElement('input');
        inpEt.type = 'hidden';
        inpEt.name = `slots[${idx}][end_time]`;
        inpEt.value = slot.end_time;
        hiddenSlotsContainer.appendChild(inpEt);
        // room
        const inpRoom = document.createElement('input');
        inpRoom.type = 'hidden';
        inpRoom.name = `slots[${idx}][room]`;
        inpRoom.value = slot.room || '';
        hiddenSlotsContainer.appendChild(inpRoom);
      });
    } catch (err) {
      console.error('Error fetching course details:', err);
      slotSummary.textContent = 'Error fetching details.';
    }
  }

  courseInput.addEventListener('change', onSectionChange);
  courseInput.addEventListener('blur', onSectionChange);

  // Initial load
  loadSections();
});