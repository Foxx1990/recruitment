## Opis tabel

`bookshop_order` - zawiera dane koszyków (zamówień) użytkowników

| Kolumna            | Opis                                                                                |
|:-------------------|:------------------------------------------------------------------------------------|
| user\_id           | identyfikator użytkownika; jeden użytkownik może posiadać wiele koszyków / zamówień |
| items\_total       | wartość wszystkich produktów w koszyku                                              |
| adjustments\_total | wartość wszystkich dodatkowych dopłat i upustów (wartość ujemna) do zamówienia      |
| total              | wartość całego zamówienia do zapłaty (suma `items_total` i `adjustments_total`)     |

`bookshop_order_item` - zawiera części składowe zamówień (przedmioty zamówień)

| Kolumna     | Opis                                                                   |
|:------------|:-----------------------------------------------------------------------|
| order\_id   | identyfikator zamówienia, do którego przypisany jest dany przedmiot    |
| product\_id | identyfikator produktu, który jest przypisany do przedmiotu zamówienia |
| quantity    | liczba sztuk                                                           |
| unit\_price | cena jednostkowa (za sztukę) przedmiotu                                |
| total       | wartość przedmiotu zamówienia, którą powinien zapłacić klient          |
| tax\_value  | wartość podatku dla danego przedmiotu zamówienia                       |

`bookshop_product` - zawiera informacje o produktach dostępnych do zakupu 

| Kolumna   | Opis                                                                             |
|:----------|:---------------------------------------------------------------------------------|
| name      | nazwa produktu                                                                   |
| code      | unikalny kod produktu                                                            |
| type      | typ produktu (`book` - książka; `audio` - produkt cyfrowy; `course` - szkolenie) |
| price     | cena bazowa produktu za sztukę                                                   |
| tax\_rate | stawka podatku podana w procentach (`null` oznacza produkt zwolniony z podatku)  |

`bookshop_promotion` - zawiera informacje o dostępnych promocjach

| Kolumna                | Opis                                                                              |
|:-----------------------|:----------------------------------------------------------------------------------|
| type                   | typ promocji (`1` - na przedmiot zamówienia; `2` - na całe zamówienie             |
| percentage\_discount   | procentowa wartość przyznanego rabatu                                             |
| product\_types\_filter | typy produktów, do których powinna zostać naliczona promocja (tylko dla `type=1`) |
