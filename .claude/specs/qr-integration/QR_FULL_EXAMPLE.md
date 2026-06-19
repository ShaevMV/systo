# QR → ORG — полный пример контракта заказа (максимальный payload)

> Канонический «максимум, что шлёт витрина» для `POST /api/v1/qrOrder/create`.
> Контракт приёма (обязательные/проецируемые/хранимые поля) — `QR_CREATE_API.md` + `QR_CREATE_RESPONSE.md`.
> Часть полей опциональна (присылаются только при наличии данных).

```jsonc
{
  "order_id": "5e9b1f2a-7c3d-4f8a-9b21-aa0c4e7d1234",  // UUID заказа (== id заказа org)
  "source": "qr.spaceofjoy.ru",
  "external_order_no": 123,            // человекочитаемый номер заказа qr

  "order_data": {
    "status": "оплачен",               // триггер выпуска: "оплачен"|"paid"
    "type_order": "regular",           // regular | friendly
    "email": "ivan@example.com",       // куда слать билеты (fallback для гостей)
    "festival": { "id": "46f5a62a-90f6-469b-9fb2-ff59d3b55e2e", "title": "СИСТО ОСЕНЬ", "event_id": 2, "start_date": "2026-09-10", "end_date": "2026-09-14" },
    "created_at": "2026-06-18T12:30:00+03:00",
    "paid_at":    "2026-06-18T12:45:00+03:00",
    "parent_order_no": null,           // если докупка опций к заказу — номер родителя
    "comment": "qr.spaceofjoy.ru заказ #123",
    "consents": { "rules": true, "privacy": true }
  },

  "payment": {
    "method": "transfer",              // transfer | online | live
    "amount_total": 12300,             // итог по заказу, ₽ (целые)
    "currency": "RUB",
    "method_details": { "title": "ЮMoney", "bank": "ЮMoney", "card_number": "410011904396730", "card_holder": null },
    "transfer": { "last4": "1234", "confirmed_at": "2026-06-18T12:45:00+03:00", "receipt_url": "https://qr.spaceofjoy.ru/uploads/receipts/abc.pdf" },
    "sales_point": null,               // для live: {id, name, address}
    "promo_codes": ["OSEN.BUDET"],
    "discounts": [ { "code": "OSEN.BUDET", "type": "fixed", "org_fee_price": 5900, "applied_to_org_fees": 2 } ]
  },

  "buyer": { "user_id": "9d6e7c10-1111-4222-8333-444455556666", "fio": "Иван Петров", "email": "ivan@example.com", "phone": "+79991234567", "city": "Казань", "telegram": "@ivan" },

  "price": { "total": 12300 },         // совместимость со старым приёмом
  "user":  { "city": "Казань", "phone": "+79991234567" },

  "guests": [
    {
      "role": "org_fee", "is_buyer": true,
      "name": "Иван Петров", "fio": "Иван Петров", "email": "ivan@example.com", "phone": "+79991234567", "city": "Казань", "telegram": "@ivan",
      "user_id": "9d6e7c10-1111-4222-8333-444455556666",
      "paid_by": { "fio": "Иван Петров", "email": "ivan@example.com" },
      "type_ticket": { "id": "222abc0c-fc8e-4a1d-a4b0-d345cafada08", "title": "Оргвзнос" },
      "price": { "unit": 5900, "total": 5900 },
      "is_co_organizer": true,
      "options": [
        { "kind": "sapling", "name": "Саженец", "ticket_type_id": null, "qty": 1, "unit_price": 500, "total": 500 },
        { "kind": "forest_card", "name": "Лесная карта", "ticket_type_id": null, "qty": 0, "unit_price": 0, "total": 0 }
      ]
    },
    {
      "role": "org_fee", "is_buyer": false,
      "name": "Пётр Смирнов", "fio": "Пётр Смирнов", "email": "petr@example.com", "phone": null, "telegram": null,
      "user_id": "b2c4a6e8-7777-4888-8999-aaaabbbbcccc",
      "paid_by": { "fio": "Иван Петров", "email": "ivan@example.com" },
      "type_ticket": { "id": "222abc0c-fc8e-4a1d-a4b0-d345cafada08", "title": "Оргвзнос" },
      "price": { "unit": 5900, "total": 5900 },
      "options": []
    },
    {
      "role": "kid",
      "name": "Маша Петрова (7 лет)",
      "parent": { "fio": "Иван Петров", "email": "ivan@example.com" },
      "type_ticket": { "id": "c3d4e5f6-aaaa-4bbb-8ccc-dddd11112222", "title": "Детский билет" },
      "price": { "unit": 1500, "total": 1500 },
      "child": { "name": "Маша Петрова", "age": 7, "allergies": "пыльца", "parent_fio": "Иван Петров", "parent_phone": "+79991234567", "trustee_fio": "Анна Сидорова", "trustee_phone": "+79990000000" }
    },
    {
      "role": "eco_car",
      "name": "А123БВ77 / автомобиль / Иван Петров",  // строка для PDF/письма
      "type_ticket": { "id": "20066a25-eeee-4fff-8000-111122223333", "title": "Парковка" },
      "price": { "unit": 1000, "total": 1000 },
      "car": { "number": "А123БВ77", "driver_fio": "Иван Петров" }
    }
  ]
}
```

## Справочник полей

**Роли гостя (`guests[].role`):** `org_fee` (оргвзнос) · `kid` (детский) · `eco_car` (парковка/эко-сбор).
**type_order:** `regular` (обычный конвейер) · `friendly` (письмо без ссылки на ЛК). Единый на заказ. Парковка = `type_ticket`, не `type_order`.
**Время** — ISO 8601 с TZ (МСК `+03:00`). **Деньги** — целые рубли.
**Опциональные блоки** (`transfer`, `sales_point`, `child`, `car`, `discounts`, `options`) — только при наличии данных.
