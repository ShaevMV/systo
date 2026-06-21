<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Колонка `description` для шаблонов (AF-3) — краткое описание шаблона для админки
 * («что это за письмо/PDF»). Видно в списке и в редакторе; в рендере НЕ участвует.
 *
 * up() заодно проставляет описания текущим шаблонам по `slug` (идемпотентно —
 * только там, где описание ещё пустое, чтобы не затереть правки админа).
 */
return new class extends Migration
{
    /** Краткие описания текущих шаблонов по slug. */
    private const DESCRIPTIONS = [
        // ── Письма по заказам ──────────────────────────────────────────────
        'orderToCreate' => 'Письмо «заказ создан» (двухшаговый qr-цикл, билеты ещё не выпущены)',
        'orderToPaid' => 'Письмо «заказ оплачен» — с PDF-билетами',
        'orderToPaidAvto' => 'Письмо об оплате заказа с авто-пропуском (парковка)',
        'orderToCancel' => 'Письмо об отмене заказа',
        'orderToChangeTicket' => 'Письмо «данные заказа изменены»',
        'orderToDifficultiesArose' => 'Письмо о трудностях с заказом',
        'orderToLiveTicketIssued' => 'Письмо «живой билет выдан»',
        'orderToPaidLiveTicket' => 'Письмо «живой билет оплачен»',
        'orderToPaidLiveTicket2' => 'Письмо «живой билет оплачен» (вариант 2)',
        'orderToPaidLiveKokaoTicket' => 'Письмо «живой билет оплачен» (лесная карта / Кокао)',
        // ── Письма по заказам-спискам ──────────────────────────────────────
        'orderListApproved' => 'Письмо «заказ-список одобрен» — PDF-билеты получателю',
        'orderListCancel' => 'Письмо об отмене заказа-списка',
        'orderListDifficultiesArose' => 'Письмо о трудностях с заказом-списком',
        // ── Письма «заказ оплачен» под конкретные типы билетов ─────────────
        'TypeTicketMailOrderToPaid1' => 'Письмо «заказ оплачен» для типа билета №1',
        'TypeTicketMailOrderToPaid2' => 'Письмо «заказ оплачен» для типа билета №2',
        'TypeTicketMailOrderToPaid3' => 'Письмо «заказ оплачен» для типа билета №3',
        'TypeTicketMailOrderToPaid20' => 'Письмо «заказ оплачен» для типа билета №20',
        'TypeTicketMailOrderToPaidChild' => 'Письмо «заказ оплачен» для детского билета',
        'TypeTicketMailOrderToPaidChildFriendly' => 'Письмо «заказ оплачен» — детский Friendly-билет',
        'TypeTicketMailOrderToPaidFriendly1' => 'Письмо «заказ оплачен» (Friendly) для типа билета №1',
        // ── Аккаунт / анкеты ───────────────────────────────────────────────
        'newUser' => 'Письмо при регистрации нового пользователя',
        'passwordResets' => 'Письмо со ссылкой для сброса пароля',
        'invate' => 'Письмо-приглашение (ссылка после одобрения анкеты)',
        'questionnaire' => 'Письмо со ссылкой на анкету гостя',
        // ── PDF-билеты ─────────────────────────────────────────────────────
        'pdf' => 'Базовый шаблон PDF-билета',
        'pdf2' => 'Базовый шаблон PDF-билета (вариант 2)',
        'TypeAvtoPdf1' => 'PDF авто-пропуска (парковка)',
        'TypeTicketPdf1' => 'PDF-билет для типа билета №1',
        'TypeTicketPdf2' => 'PDF-билет для типа билета №2',
        'TypeTicketPdf3' => 'PDF-билет для типа билета №3',
        'TypeTicketPdf4' => 'PDF-билет для типа билета №4',
        'TypeTicketPdfChild' => 'PDF детского билета',
        'TypeTicketPdfList' => 'PDF-билет по заказу-списку',
    ];

    public function up(): void
    {
        Schema::table('templates', static function (Blueprint $table): void {
            $table->string('description', 500)->nullable()->after('title');
        });

        foreach (self::DESCRIPTIONS as $slug => $description) {
            DB::table('templates')
                ->where('slug', $slug)
                ->where(static function ($query): void {
                    $query->whereNull('description')->orWhere('description', '');
                })
                ->update(['description' => $description]);
        }
    }

    public function down(): void
    {
        Schema::table('templates', static function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
