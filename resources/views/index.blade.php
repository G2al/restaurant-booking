<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
    <title>Prenota un Tavolo - Ristorante</title>
    <!-- Bootstrap css -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <!-- Custom css -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Booking Section -->
        <div class="search-card">
            <h6 class="text-primary-color mb-3 fw-semibold">
                <i class="bi bi-calendar-check me-2"></i>Prenota il tuo Tavolo
            </h6>
            <form id="bookingForm">
                <!-- Step 1: Data -->
                <div class="mb-3">
                    <label for="bookingDate" class="form-label">
                        <i class="bi bi-calendar3 me-1 text-primary-color"></i>Data
                    </label>
                    <input type="date" class="form-control" id="bookingDate" required>
                    <div class="form-text" id="dateHelp">Seleziona una data disponibile</div>
                </div>

                <!-- Step 2: Persone -->
                <div class="mb-3" id="guestsSection" style="display: none;">
                    <label for="guestsCount" class="form-label">
                        <i class="bi bi-people me-1 text-secondary-color"></i>Numero Persone
                    </label>
                    
                    <!-- Dropdown personalizzato -->
                    <div class="custom-dropdown" id="guestsDropdown">
                        <div class="custom-dropdown-trigger" id="guestsDropdownTrigger">
                            <span class="custom-dropdown-text placeholder" id="guestsDropdownText">Seleziona numero persone</span>
                            <i class="bi bi-chevron-down custom-dropdown-arrow"></i>
                        </div>
                        <div class="custom-dropdown-menu" id="guestsDropdownMenu">
                            <!-- Contenuto placeholder iniziale -->
                            <div class="custom-dropdown-item disabled-item">
                                <i class="bi bi-calendar me-2"></i>Seleziona prima una data
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo hidden per mantenere compatibilità con il JavaScript esistente -->
                    <input type="hidden" id="guestsCount" required>
                </div>

                <!-- Step 3: Orario -->
                <div class="mb-3" id="timeSection" style="display: none;">
                    <label for="bookingTime" class="form-label">
                        <i class="bi bi-clock me-1 text-accent-color"></i>Orario
                    </label>
                    
                    <!-- Dropdown personalizzato -->
                    <div class="custom-dropdown" id="timeDropdown">
                        <div class="custom-dropdown-trigger" id="timeDropdownTrigger">
                            <span class="custom-dropdown-text placeholder" id="timeDropdownText">Seleziona prima data e persone</span>
                            <i class="bi bi-chevron-down custom-dropdown-arrow"></i>
                        </div>
                        
                        <div class="custom-dropdown-menu" id="timeDropdownMenu">
                            <!-- Contenuto placeholder iniziale -->
                            <div class="custom-dropdown-item disabled-item">
                                <i class="bi bi-clock me-2"></i>Seleziona prima persone
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo hidden per mantenere compatibilità -->
                    <input type="hidden" id="bookingTime" required>
                </div>

                <!-- Step 4: Dati Cliente -->
                <div id="customerSection" style="display: none;">
                    <div class="mb-3">
                        <label for="customerName" class="form-label">
                            <i class="bi bi-person me-1 text-primary-color"></i>Nome e Cognome
                        </label>
                        <input type="text" class="form-control" id="customerName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="customerEmail" class="form-label">
                            <i class="bi bi-envelope me-1 text-secondary-color"></i>Email
                        </label>
                        <input type="email" class="form-control" id="customerEmail" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="customerPhone" class="form-label">
                            <i class="bi bi-telephone me-1 text-accent-color"></i>Telefono
                        </label>
                        <input type="tel" class="form-control" id="customerPhone" required>
                    </div>

                    <div class="mb-3">
                        <label for="specialRequests" class="form-label">
                            <i class="bi bi-chat-dots me-1 text-primary-color"></i>Richieste Speciali (opzionale)
                        </label>
                        <textarea class="form-control" id="specialRequests" rows="3" 
                                placeholder="Es: Seggiolone per bambino, decorazioni compleanno, tavolo vicino finestra..."></textarea>
                        <div class="form-text">Campo facoltativo per richieste particolari</div>
                    </div>
                </div>

                <button type="submit" class="btn-app p-2 fw-bold btn" id="submitBtn" disabled>
                    <i class="bi bi-check-circle me-2"></i>Conferma Prenotazione
                </button>
            </form>

            <!-- Success Message -->
            <div id="successMessage" class="alert alert-success mt-3" style="display: none;">
                <i class="bi bi-check-circle me-2"></i>
                <span id="successText"></span>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="alert alert-danger mt-3" style="display: none;">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <span id="errorText"></span>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/script.js') }}"></script>
</body>

</html>