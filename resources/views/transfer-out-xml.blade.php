<Файл ИдФайл="{{ $fileId }}" ВерсФорм="5.01" ВерсПрог="epricing.v0">
    <СвУчДокОбор ИдОтпр="{{ $seller->edoId }}" ИдПок="{{ $buyer->edoId }}">
        <СвОЭДОтпрСФ ИННЮЛ="7605016030" ИдЭДОСФ="2BE" НаимОрг="ООО {{ '"Компания "Тензор""' }}"/>
    </СвУчДокОбор>
    <Документ ВремИнфПр="{{ \Carbon\Carbon::now('+07:00')->format('H.i.s') }}"
              ДатаИнфПр="{{ \Carbon\Carbon::now('+07:00')->format('d.m.Y') }}"
              КНД="1115131"
              НаимДокОпр="Счет-фактура и документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)"
              НаимЭконСубСост="{{ $seller->name }}, ИНН/КПП {{ $seller->inn  }}/{{ $seller->kpp }}"
              ПоФактХЖ="Документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)"
              Функция="СЧФДОП"
    >
        <СвСчФакт ДатаСчФ="{{ $doc->date }}" КодОКВ="643" НомерСчФ="{{ $doc-> number }}">
            <СвПрод ОКПО="{{ $seller->okpo }}">
                <ИдСв>
                    <СвЮЛУч ИННЮЛ="{{ $seller->inn }}" КПП="{{ $seller->kpp }}" НаимОрг="{{ $seller->name }}"/>
                </ИдСв>
                <Адрес>
                    <АдрИнф АдрТекст="{{ $seller->address }}" КодСтр="643"/>
                </Адрес>
                <Контакт Тлф="{{ $seller->phone }}" ЭлПочта="{{ $seller->email }}"/>
                <БанкРекв НомерСчета="{{ $seller->account }}">
                    <СвБанк БИК="{{ $seller->bic }}" КорСчет="{{ $seller->korr }}" НаимБанк="{{ $seller->bank }}"/>
                </БанкРекв>
            </СвПрод>
            <ГрузОт>
                <ОнЖе>он же</ОнЖе>
            </ГрузОт>
            <ГрузПолуч>
                <ИдСв>
                    <СвЮЛУч ИННЮЛ="{{ $buyer->inn }}" КПП="{{ $buyer->kpp }}" НаимОрг="{{ $buyer->gruzName }}"/>
                </ИдСв>
                <Адрес>
                    <АдрИнф АдрТекст="{{ $buyer->gruzAddress }}" КодСтр="643"/>
                </Адрес>
                <Контакт Тлф="{{ $buyer->phone }}"/>
                <БанкРекв НомерСчета="{{ $buyer->account }}">
                    <СвБанк БИК="{{ $buyer->bic }}" КорСчет="{{ $buyer->korr }}" НаимБанк="{{ $buyer->bank }}"/>
                </БанкРекв>
            </ГрузПолуч>
            <СвПокуп>
                <ИдСв>
                    <СвЮЛУч ИННЮЛ="{{ $buyer->inn }}" КПП="{{ $buyer->kpp }}" НаимОрг="{{ $buyer->name }}"/>
                </ИдСв>
                <Адрес>
                    <АдрИнф АдрТекст="{{ $buyer->address }}" КодСтр="643"/>
                </Адрес>
                <Контакт Тлф="{{ $buyer->phone }}"/>
                <БанкРекв НомерСчета="{{ $buyer->account }}">
                    <СвБанк БИК="{{ $buyer->bic }}" КорСчет="{{ $buyer->korr }}" НаимБанк="{{ $buyer->bank }}"/>
                </БанкРекв>
            </СвПокуп>
        </СвСчФакт>
        <ТаблСчФакт>
            @foreach($doc->lines as $line)
                <СведТов КолТов="{{ $line->quantity }}"
                         НаимТов="{{ $line->name }}"
                         НалСт="20%"
                         НомСтр="{{ $loop->iteration }}"
                         ОКЕИ_Тов="{{ $line->unit }}"
                         СтТовБезНДС="{{ $line->amountWithoutVat }}"
                         СтТовУчНал="{{ $line->amountWithVat }}"
                         ЦенаТов="{{ $line->priceWithoutVat }}">
                    <Акциз>
                        <БезАкциз>без акциза</БезАкциз>
                    </Акциз>
                    <СумНал>
                        <СумНал>{{ $line->amountVat }}</СумНал>
                    </СумНал>
                    @if ($line->countryNumCode)
                        <СвТД КодПроисх="{{ $line->countryNumCode }}" НомерТД="{{ $line->gtd }}"/>
                    @endif
                    <ДопСведТов
                        КодТов="{{ $line->code }}"
                        @if ($line->countryNumCode)
                        КрНаимСтрПр="{{ $line->countryName }}"
                        @endif
                        НаимЕдИзм="{{ $line->unitName }}"
                        ПрТовРаб="1"
                    />
                </СведТов>
            @endforeach
            <ВсегоОпл СтТовБезНДСВсего="{{ $doc->sumWithoutVat }}" СтТовУчНалВсего="{{ $doc->sumWithVat }}">
                <СумНалВсего>
                    <СумНал>{{ $doc->sumVat }}</СумНал>
                </СумНалВсего>
            </ВсегоОпл>
        </ТаблСчФакт>
        <СвПродПер>
            <СвПер ВидОпер="Продажа" СодОпер="Товары переданы">
                <ОснПер ДатаОсн="{{ $doc->invoiceDate }}"
                        НаимОсн="Счет"
                        НомОсн="{{ $doc->invoiceNumber }}"
                        ДопСвОсн="Заказ GPC-00000{{ $doc->orderNumber }}"
                />
            </СвПер>
        </СвПродПер>
        <Подписант ОблПолн="5" ОснПолн="Должностные обязанности" Статус="1">
            <ЮЛ Должн="ДИРЕКТОР" ИННЮЛ="{{ $seller->inn }}" НаимОрг="{{ $seller->name }}">
                <ФИО Имя="Михаил" Отчество="Сергеевич" Фамилия="Верхотуров"/>
            </ЮЛ>
        </Подписант>
    </Документ>
</Файл>
