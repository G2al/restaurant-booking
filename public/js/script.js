document.addEventListener('DOMContentLoaded', function() {
   const bookingDate = document.getElementById('bookingDate');
   const guestsCount = document.getElementById('guestsCount');
   const bookingTime = document.getElementById('bookingTime');
   const customerName = document.getElementById('customerName');
   const customerEmail = document.getElementById('customerEmail');
   const customerPhone = document.getElementById('customerPhone');
   const specialRequests = document.getElementById('specialRequests');
   const bookingForm = document.getElementById('bookingForm');
   const submitBtn = document.getElementById('submitBtn');
   
   const guestsSection = document.getElementById('guestsSection');
   const timeSection = document.getElementById('timeSection');
   const customerSection = document.getElementById('customerSection');
   
   const successMessage = document.getElementById('successMessage');
   const errorMessage = document.getElementById('errorMessage');
   const successText = document.getElementById('successText');
   const errorText = document.getElementById('errorText');

   const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

   // Array per memorizzare le date disponibili
   let availableDates = [];

   function setupCalendar() {
       // Imposta data minima (oggi) e massima (30 giorni da oggi)
       const today = new Date();
       const maxDate = new Date();
       maxDate.setDate(today.getDate() + 30);
       
       bookingDate.min = today.toISOString().split('T')[0];
       bookingDate.max = maxDate.toISOString().split('T')[0];
       
       // Carica le date disponibili
       loadAvailableDates();
   }

   function loadAvailableDates() {
       fetch('/api/available-dates')
           .then(response => response.json())
           .then(data => {
               // Memorizza solo gli array delle date (es: ['2025-08-09', '2025-08-10'])
               availableDates = data.map(dateItem => dateItem.date);
               
               // Aggiorna il testo di aiuto
               const dateHelp = document.getElementById('dateHelp');
               if (dateHelp) {
                   dateHelp.textContent = `${availableDates.length} date disponibili nei prossimi 30 giorni`;
               }
           })
           .catch(error => {
               console.error('Errore caricamento date:', error);
               showError('Errore nel caricamento delle date disponibili');
           });
   }

   function showError(message) {
       errorText.textContent = message;
       errorMessage.style.display = 'block';
       successMessage.style.display = 'none';
   }

   function loadAvailableCapacities(date) {
       // Aggiorna dropdown personalizzato invece del select
       const dropdownText = document.getElementById('guestsDropdownText');
       const dropdownMenu = document.getElementById('guestsDropdownMenu');
       
       if (dropdownText) {
           dropdownText.textContent = 'Caricamento...';
           dropdownText.classList.add('placeholder');
       }
       
       guestsSection.style.display = 'block';
       
       fetch(`/api/available-capacities?date=${date}`)
           .then(response => response.json())
           .then(data => {
               if (dropdownText) {
                   dropdownText.textContent = 'Seleziona numero persone';
                   dropdownText.classList.add('placeholder');
               }
               
               // Pulisci e popola dropdown menu
               if (dropdownMenu) {
                   dropdownMenu.innerHTML = '';
                   
                   if (data.length === 0) {
                       if (dropdownText) {
                           dropdownText.textContent = 'Nessuna disponibilità per questa data';
                       }
                       showError('Non ci sono tavoli disponibili per la data selezionata.');
                   } else {
                       data.forEach(capacity => {
                           const item = document.createElement('div');
                           item.className = 'custom-dropdown-item';
                           item.setAttribute('data-value', capacity);
                           item.innerHTML = `<i class="bi bi-people me-2"></i>${capacity} ${capacity === 1 ? 'Persona' : 'Persone'}`;
                           dropdownMenu.appendChild(item);
                       });
                       
                       errorMessage.style.display = 'none';
                   }
               }
           })
           .catch(error => {
               console.error('Errore caricamento capacità:', error);
               showError('Errore nel caricamento delle disponibilità');
           });
   }

   function loadAvailableTimes(date, guests) {
       // Aggiorna dropdown personalizzato invece del select
       const timeDropdownText = document.getElementById('timeDropdownText');
       const timeDropdownMenu = document.getElementById('timeDropdownMenu');
       
       if (timeDropdownText) {
           timeDropdownText.textContent = 'Caricamento orari...';
           timeDropdownText.classList.add('placeholder');
       }
       
       timeSection.style.display = 'block';
       
       fetch(`/api/available-times?date=${date}&guests=${guests}`)
           .then(response => response.json())
           .then(data => {
               if (timeDropdownText) {
                   timeDropdownText.textContent = 'Seleziona un orario';
                   timeDropdownText.classList.add('placeholder');
               }
               
               // Pulisci e popola dropdown menu orari
               if (timeDropdownMenu) {
                   timeDropdownMenu.innerHTML = '';
                   
                   if (data.length === 0) {
                       if (timeDropdownText) {
                           timeDropdownText.textContent = 'Nessun orario disponibile';
                       }
                       showError(`Non ci sono tavoli disponibili per ${guests} persone in questa data.`);
                       customerSection.style.display = 'none';
                       submitBtn.disabled = true;
                   } else {
                       data.forEach(timeSlot => {
                           const item = document.createElement('div');
                           item.className = 'custom-dropdown-item';
                           item.setAttribute('data-value', timeSlot.id);
                           item.innerHTML = `<i class="bi bi-clock me-2"></i>${timeSlot.formatted}`;
                           timeDropdownMenu.appendChild(item);
                       });
                       
                       errorMessage.style.display = 'none';
                   }
               }
           })
           .catch(error => {
               console.error('Errore caricamento orari:', error);
               showError('Errore nel caricamento degli orari disponibili');
               if (timeDropdownText) {
                   timeDropdownText.textContent = 'Errore nel caricamento';
               }
           });
   }

   // Event listener per il calendario
   bookingDate.addEventListener('change', function() {
       const selectedDate = this.value;
       
       if (selectedDate) {
           // Verifica se la data selezionata è disponibile
           if (!availableDates.includes(selectedDate)) {
               this.value = ''; // Reset del campo
               showError('Data non disponibile. Seleziona una data diversa.');
               
               // Nasconde le sezioni successive
               guestsSection.style.display = 'none';
               timeSection.style.display = 'none';
               customerSection.style.display = 'none';
               submitBtn.disabled = true;
               return;
           }
           
           // Data valida, procedi con il caricamento
           loadAvailableCapacities(selectedDate);
           
           timeSection.style.display = 'none';
           customerSection.style.display = 'none';
           
           // Reset dropdown orario
           const timeDropdownText = document.getElementById('timeDropdownText');
           if (timeDropdownText) {
               timeDropdownText.textContent = 'Seleziona prima numero persone';
               timeDropdownText.classList.add('placeholder');
           }
           
           guestsCount.value = '';
           bookingTime.value = '';
           
           submitBtn.disabled = true;
           
           successMessage.style.display = 'none';
           errorMessage.style.display = 'none';
       } else {
           guestsSection.style.display = 'none';
           timeSection.style.display = 'none';
           customerSection.style.display = 'none';
           submitBtn.disabled = true;
       }
   });

   guestsCount.addEventListener('change', function() {
       const selectedDate = bookingDate.value;
       const selectedGuests = this.value;
       
       if (selectedDate && selectedGuests) {
           loadAvailableTimes(selectedDate, selectedGuests);
       } else {
           timeSection.style.display = 'none';
           customerSection.style.display = 'none';
           
           const timeDropdownText = document.getElementById('timeDropdownText');
           if (timeDropdownText) {
               timeDropdownText.textContent = 'Seleziona prima data e persone';
               timeDropdownText.classList.add('placeholder');
           }
           
           submitBtn.disabled = true;
       }
   });

    bookingTime.addEventListener('change', function() {
        const selectedTime = this.value;
        
        if (selectedTime) {
            customerSection.style.display = 'block';
            submitBtn.disabled = false;
            
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
        } else {
            customerSection.style.display = 'none';
            submitBtn.disabled = true;
        }
    });

    bookingForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        date: bookingDate.value,
        time_slot_id: bookingTime.value,
        guests: guestsCount.value,
        customer_name: customerName.value,
        customer_email: customerEmail.value,
        customer_phone: customerPhone.value,
        special_requests: specialRequests.value
    };
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Prenotando...';

    // Disabilita tutti i campi durante l'invio
    bookingDate.disabled = true;
    guestsCount.disabled = true;
    bookingTime.disabled = true;
    customerName.disabled = true;
    customerEmail.disabled = true;
    customerPhone.disabled = true;
    specialRequests.disabled = true;
    
    fetch('/api/bookings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successText.textContent = `${data.message} Tavolo: ${data.table}`;
            successMessage.style.display = 'block';
            errorMessage.style.display = 'none';
            
            bookingForm.reset();
            guestsSection.style.display = 'none';
            timeSection.style.display = 'none';
            customerSection.style.display = 'none';
            
            // Reset dropdown personalizzati
            const guestsDropdownText = document.getElementById('guestsDropdownText');
            if (guestsDropdownText) {
                guestsDropdownText.textContent = 'Seleziona numero persone';
                guestsDropdownText.classList.add('placeholder');
            }
            
            const timeDropdownText = document.getElementById('timeDropdownText');
            if (timeDropdownText) {
                timeDropdownText.textContent = 'Seleziona prima data e persone';
                timeDropdownText.classList.add('placeholder');
            }
            
            // Ricarica le date disponibili
            loadAvailableDates();
        } else {
            showError(data.error || 'Errore durante la prenotazione');
        }
    })
    .catch(error => {
        console.error('Errore prenotazione:', error);
        showError('Errore durante la prenotazione. Riprova.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Conferma Prenotazione';
        
        // Riabilita tutti i campi
        bookingDate.disabled = false;
        guestsCount.disabled = false;
        bookingTime.disabled = false;
        customerName.disabled = false;
        customerEmail.disabled = false;
        customerPhone.disabled = false;
        specialRequests.disabled = false;
    });
});

   // ===== DROPDOWN PERSONALIZZATI =====
   function initCustomDropdown(triggerId, menuId, hiddenInputId) {
       const trigger = document.getElementById(triggerId);
       const menu = document.getElementById(menuId);
       const hiddenInput = document.getElementById(hiddenInputId);
       const dropdownText = trigger.querySelector('.custom-dropdown-text');
       const dropdown = trigger.closest('.custom-dropdown');
       
       // Apri/chiudi dropdown
       trigger.addEventListener('click', function(e) {
           e.stopPropagation();
           
           // Chiudi altri dropdown aperti
           document.querySelectorAll('.custom-dropdown.open').forEach(openDropdown => {
               if (openDropdown !== dropdown) {
                   openDropdown.classList.remove('open');
               }
           });
           
           // Toggle questo dropdown
           dropdown.classList.toggle('open');
       });
       
       // Selezione item
       menu.addEventListener('click', function(e) {
           const item = e.target.closest('.custom-dropdown-item');
           if (!item) return;
           
           const value = item.getAttribute('data-value');
           const text = item.textContent.trim();
           
           // Aggiorna UI
           dropdownText.textContent = text;
           dropdownText.classList.remove('placeholder');
           
           // Rimuovi selezione precedente
           menu.querySelectorAll('.custom-dropdown-item').forEach(i => i.classList.remove('selected'));
           // Aggiungi selezione corrente
           item.classList.add('selected');
           
           // Aggiorna campo hidden
           hiddenInput.value = value;
           
           // Trigger change event per compatibilità con codice esistente
           hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
           
           // Chiudi dropdown
           dropdown.classList.remove('open');
       });
       
       // Chiudi dropdown cliccando fuori
       document.addEventListener('click', function(e) {
           if (!dropdown.contains(e.target)) {
               dropdown.classList.remove('open');
           }
       });
       
       // Chiudi dropdown con ESC
       document.addEventListener('keydown', function(e) {
           if (e.key === 'Escape') {
               dropdown.classList.remove('open');
           }
       });
   }

   // Inizializza dropdown persone
   initCustomDropdown('guestsDropdownTrigger', 'guestsDropdownMenu', 'guestsCount');
   
   // Inizializza dropdown orario
   initCustomDropdown('timeDropdownTrigger', 'timeDropdownMenu', 'bookingTime');

   // Inizializza il calendario
   setupCalendar();
});