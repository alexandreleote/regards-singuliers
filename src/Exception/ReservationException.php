<?php

namespace App\Exception;

class ReservationException extends \RuntimeException
{
    public const INVALID_DATA = 'Données invalides pour la réservation';
    public const SERVICE_NOT_FOUND = 'Service non trouvé';
    public const PROFILE_INCOMPLETE = 'Veuillez compléter votre profil avant de faire une réservation';
    public const RESERVATION_NOT_FOUND = 'Réservation non trouvée';
    public const PAYMENT_ERROR = 'Erreur lors du paiement';
    public const CANCELLATION_ERROR = 'Erreur lors de l\'annulation';
} 