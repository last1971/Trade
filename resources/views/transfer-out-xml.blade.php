<Файл ИдФайл="{{ $fileId }}" ВерсФорм="5.01" ВерсПрог="epricing.v1">
    <СвУчДокОбор ИдОтпр="{{ $transferOut->firm->EDOID }}"
                 ИдПок="{{ $transferOut->buyer->advancedBuyer->edo_id ?? $transferOut->buyer->Inn }}"
    >
        <СвОЭДОтпрСФ ИННЮЛ="7605016030" ИдЭДОСФ="2BE" НаимОрг="ООО {{ '"Компания "Тензор""' }}"/>
    </СвУчДокОбор>
    <Документ ВремИнфПр="{{ \Carbon\Carbon::now('+07:00')->format('H.i.s') }}"
              ДатаИнфПр="{{ \Carbon\Carbon::now('+07:00')->format('d.m.Y') }}"
              КНД="1115131"
              НаимДокОпр="Счет-фактура и документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)"
              НаимЭконСубСост="{{ $transferOut->firm->FIRMNAME }}, ИНН/КПП {{ $transferOut->firm->getAttributes()['INN'] }}"
              ПоФактХЖ="Документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)"
              Функция="СЧФДОП"
    >
        <СвСчФакт ДатаСчФ="{{ \Carbon\Carbon::create($transferOut->DATA)->format('d.m.Y') }}"
                  КодОКВ="643"
                  НомерСчФ="{{ $transferOut->NSF }}"
        >
            <СвПрод ОКПО="{{ $transferOut->firm->OKPO }}">
                <ИдСв>
                    <СвЮЛУч ИННЮЛ="{{ $transferOut->firm->Inn }}"
                            КПП="{{ $transferOut->firm->Kpp }}"
                            НаимОрг="{{ $transferOut->firm->FIRMNAME }}"
                    />
                </ИдСв>
                <Адрес>
                    <АдрИнф АдрТекст="{{ $transferOut->firm->ADDRESS }}" КодСтр="643"/>
                </Адрес>
                <Контакт Тлф="{{ $transferOut->firm->PHONES }}" ЭлПочта="{{ $transferOut->firm->EMAIL }}"/>
                <БанкРекв
                    НомерСчета="{{ $transferOut->firmHistory ? $transferOut->firmHistory->RS : $transferOut->firm->RS }}">
                    <СвБанк
                        БИК="{{ $transferOut->firmHistory ?  $transferOut->firmHistory->BIK : $transferOut->firm->BIK }}"
                        КорСчет="{{ $transferOut->firmHistory ? $transferOut->firmHistory->KS : $transferOut->firm->KS }}"
                        НаимБанк="{{ $transferOut->firmHistory ? $transferOut->firmHistory->BANK : $transferOut->firm->BANK }}"
                    />
                </БанкРекв>
            </СвПрод>
            <ГрузОт>
                <ОнЖе>он же</ОнЖе>
            </ГрузОт>
            <ГрузПолуч>
                <ИдСв>
                    <СвЮЛУч ИННЮЛ="{{ $transferOut->buyer->Inn }}"
                            КПП="{{ $transferOut->buyer->Kpp }}"
                            НаимОрг="{{ $transferOut->buyer->advancedBuyer->consignee ?? $transferOut->buyer->FULLNAME }}"
                    />
                </ИдСв>
                <Адрес>
                    <АдрИнф
                        АдрТекст="{{ $transferOut->buyer->advancedBuyer->consigneeAddress ?? $transferOut->buyer->ADDRESS }}"
                        КодСтр="643"
                    />
                </Адрес>
                <Контакт Тлф="{{ $transferOut->buyer->PHONES }}"/>
                <БанкРекв НомерСчета="{{ $transferOut->buyer->RSCHET1 }}">
                    <СвБанк БИК="{{ $transferOut->buyer->BIK }}"
                            КорСчет="{{ $transferOut->buyer->KS }}"
                            НаимБанк="{{ $transferOut->buyer->BANK }}"
                    />
                </БанкРекв>
            </ГрузПолуч>
            @foreach($cashFlows as $cf)
                <СвПРД НомерПРД="{{ $cf->NPP }}"
                       ДатаПРД="{{ (new Date($cf->DATA))->format('d.m.Y') }}"
                       СуммаПРД="{{ $cf->MONEYSCHET }}"
                />
            @endforeach
            <СвПокуп>
                <ИдСв>
                    <СвЮЛУч ИННЮЛ="{{ $transferOut->buyer->Inn }}"
                            КПП="{{ $transferOut->buyer->Kpp }}"
                            НаимОрг="{{ $transferOut->buyer->FULLNAME }}"
                    />
                </ИдСв>
                <Адрес>
                    <АдрИнф АдрТекст="{{ $transferOut->buyer->ADDRESS }}" КодСтр="643"/>
                </Адрес>
                <Контакт Тлф="{{ $transferOut->buyer->PHONES }}"/>
                <БанкРекв НомерСчета="{{ $transferOut->buyer->RSCHET1 }}">
                    <СвБанк БИК="{{ $transferOut->buyer->BIK }}"
                            КорСчет="{{ $transferOut->buyer->KS }}"
                            НаимБанк="{{ $transferOut->buyer->BANK }}"
                    />
                </БанкРекв>
            </СвПокуп>
        </СвСчФакт>
        <ТаблСчФакт>
            @foreach($transferOutLines as $line)
                <СведТов КолТов="{{ $line->QUAN }}"
                         НаимТов="{{ $line->name->NAME }}"
                         НалСт="{{ VAT::get($transferOut->DATA) }}%"
                         НомСтр="{{ $loop->iteration }}"
                         ОКЕИ_Тов="{{ $line->good->unitCode }}"
                         СтТовБезНДС="{{ $line->amountWithoutVat }}"
                         СтТовУчНал="{{ $line->SUMMAP }}"
                         ЦенаТов="{{ $line->priceWithoutVat }}">
                    <Акциз>
                        <БезАкциз>без акциза</БезАкциз>
                    </Акциз>
                    <СумНал>
                        <СумНал>{{ str_replace(',', '.', $line->SUMMAP - $line->amountWithoutVat) }}</СумНал>
                    </СумНал>
                    @if ($line->countryNumCode)
                        <СвТД КодПроисх="{{ $line->countryNumCode }}" НомерТД="{{ $line->GTD }}"/>
                    @endif
                    <ДопСведТов
                        КодТов="{{ $line->GOODSCODE }}"
                        @if ($line->countryNumCode)
                        КрНаимСтрПр="{{ $line->STRANA }}"
                        @endif
                        НаимЕдИзм="{{ $line->good->unitName }}"
                        ПрТовРаб="1"
                    />
                </СведТов>
            @endforeach
            <ВсегоОпл СтТовБезНДСВсего="{{ str_replace( ',', '.', $transferOutLines->sum('amountWithoutVat')) }}"
                      СтТовУчНалВсего="{{ str_replace( ',', '.', $transferOutLines->sum('SUMMAP')) }}"
            >
                <СумНалВсего>
                    <СумНал>{{ str_replace(',', '.', $transferOutLines->sum('SUMMAP') - $transferOutLines->sum('amountWithoutVat')) }}</СумНал>
                </СумНалВсего>
            </ВсегоОпл>
        </ТаблСчФакт>
        <СвПродПер>
            <СвПер ВидОпер="Продажа" СодОпер="Товары переданы">
                <ОснПер ДатаОсн="{{ \Carbon\Carbon::create($transferOut->invoice->DATA)->format('d.m.Y') }}"
                        НаимОсн="Счет"
                        НомОсн="{{ $transferOut->invoice->NS }}"
                        @if ($transferOut->invoice->NZ)
                        ДопСвОсн="GPC-00000{{ $transferOut->invoice->NZ }}"
                    @endif
                />
            </СвПер>
        </СвПродПер>
        <Подписант ОблПолн="5" ОснПолн="Должностные обязанности" Статус="1">
            <ЮЛ Должн="ДИРЕКТОР"
                ИННЮЛ="{{ $transferOut->firm->Inn }}"
                НаимОрг="{{ $transferOut->firm->FIRMNAME }}"
            >
                <ФИО Имя="Михаил" Отчество="Сергеевич" Фамилия="Верхотуров"/>
            </ЮЛ>
        </Подписант>
    </Документ>
</Файл>
