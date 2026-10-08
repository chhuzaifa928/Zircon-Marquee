# Zircon Marquee — Accounting Posting Policy

> **Status: DEFINED by the owner (2026-10-09).** Auto-posting is wired to these
> rules in Step 4b, pending sign-off on the implementation design (below). Every
> posting is a two-sided voucher (RV/EV/PV); debit = credit.

## Chart-of-accounts code scheme

- **1000 Assets** — 1010 Cash, 1020 Petty Cash, 110x Banks (Faisal 1101, HBL 1102…),
  12xx Staff/Holding, 1500 Inventory, 1600 Fixed Assets
- **2000 Liabilities** — 2100 Customer Advances, 2200 GST Payable, 23xx Suppliers Payable
- **3000 Equity/Capital**
- **4000 Income** — 4100 Food Sales, 4200 Hall Charges, 4300 AC/Heating, 4400 DJ,
  4500 Decor Sale, 4600 Cold Drink & Water, 4900 Other Charges
- **5000 Expenses** — 5600 Event Consumables + Meat, Spices, Salary, Rent, Grocery,
  Utilities, Fuel, Maintenance…

Normal sides: cash/bank/staff/asset/expense = **debit**; liability/equity/income/supplier = **credit**.

## Postings

1. **Payment slip (RV):** Dr Cash/Bank (received into) · Cr Customer Advances (2100).
   Gross amount (incl. GST). Updates booking balance. *(wires the Step 3 slip)*
2. **Close Event / revenue recognition** (after event, fully paid):
   - Dr Customer Advances (2100) — gross total
   - Cr each income head by charge type, **net of GST**
   - Cr GST Payable (2200) — the GST amount
   - Debit = Credit. This is also when costing is entered and profit finalised.
3. **Expense (EV):** Dr Expense head · Cr Cash/Bank/Supplier.
4. **Pay a supplier (PV):** Dr Supplier Payable · Cr Cash/Bank.
5. **Inventory purchase:** Dr Inventory (1500) · Cr Cash/Bank/Supplier.
6. **Inventory consumed for event:** Dr item's default expense head (fallback
   Event Consumables 5600) · Cr Inventory (1500).
7. **Pay GST to FBR (PV):** Dr GST Payable (2200) · Cr Cash/Bank.
8. **Transfer between accounts:** Dr destination · Cr source.

## Charge-type → income-head map

| Charge type | Income head |
|-------------|-------------|
| menu / food (per-head) | 4100 Food Sales |
| hall_charges | 4200 Hall Charges |
| ac_heating | 4300 AC/Heating |
| dj | 4400 DJ |
| decor | 4500 Decor Sale |
| cold_drinks, mineral_water | 4600 Cold Drink & Water |
| other_charges | 4900 Other Charges |
| GST portion | 2200 GST Payable |

(Implemented in code as `ChartOfAccountsSeeder::CHARGE_INCOME_MAP`.)

## Build target

A **Close Event** action (Accounts-only; booking must be fully paid) posts
posting #2 and opens costing. See the implementation design presented for
sign-off before wiring.
