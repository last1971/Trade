<Файл ИдФайл="{{ $fileId }}" ВерсФорм="5.03" ВерсПрог="epricing.v1">
    <Документ ВремИнфПр="{{ \Carbon\Carbon::now('+07:00')->format('H.i.s') }}"
              ДатаИнфПр="{{ \Carbon\Carbon::now('+07:00')->format('d.m.Y') }}"
              КНД="1115131"
              Функция="СЧФДОП"
              ПоФактХЖ="Документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)"
              НаимДокОпр="Счет-фактура и документ об отгрузке товаров (выполнении работ), передаче имущественных прав (документ об оказании услуг)"
              НаимЭконСубСост="{{ $transferOut->firm->FIRMNAME }}, ИНН/КПП {{ $transferOut->firm->getAttributes()['INN'] }}"
    >
        <СвСчФакт ДатаДок="{{ \Carbon\Carbon::create($transferOut->DATA)->format('d.m.Y') }}"
                  НомерДок="{{ $transferOut->NSF }}"
        >
            <СвПрод ОКПО="{{ $transferOut->firm->OKPO }}">
                <ИдСв>
                    <СвЮЛУч ИННЮЛ="{{ $transferOut->firm->Inn }}"
                            КПП="{{ $transferOut->firm->Kpp }}"
                            НаимОрг="{{ $transferOut->firm->FIRMNAME }}"
                    />
                </ИдСв>
                <Адрес>
                    <АдрИнф АдрТекст="{{ $transferOut->firm->ADDRESS }}" КодСтр="643" НаимСтран="РОССИЯ" />
                </Адрес>
                <БанкРекв
                    НомерСчета="{{ $transferOut->firmHistory ? $transferOut->firmHistory->RS : $transferOut->firm->RS }}">
                    <СвБанк
                        БИК="{{ $transferOut->firmHistory ?  $transferOut->firmHistory->BIK : $transferOut->firm->BIK }}"
                        КорСчет="{{ $transferOut->firmHistory ? $transferOut->firmHistory->KS : $transferOut->firm->KS }}"
                        НаимБанк="{{ $transferOut->firmHistory ? $transferOut->firmHistory->BANK : $transferOut->firm->BANK }}"
                    />
                </БанкРекв>
                <Контакт>
                    <Тлф>{{ $transferOut->firm->PHONES }}</Тлф>
                    <ЭлПочта>{{ $transferOut->firm->EMAIL }}</ЭлПочта>
                </Контакт>
            </СвПрод>
            <ГрузОт>
                <ОнЖе>он же</ОнЖе>
            </ГрузОт>
            <ГрузПолуч>
                <ИдСв>
                    @if (strlen($transferOut->buyer->Inn) == 12)
                    @php
                        $fio = preg_replace('/^ИП\s+/ui', '', trim($transferOut->buyer->advancedBuyer->consignee ?? $transferOut->buyer->FULLNAME));
                        $parts = preg_split('/\s+/', $fio);
                        $lastName = $parts[0] ?? '';
                        $firstName = $parts[1] ?? '';
                        $middleName = $parts[2] ?? '';
                    @endphp
                    <СвИП ИННФЛ="{{ $transferOut->buyer->Inn }}">
                        <ФИО Имя="{{ $firstName }}" Отчество="{{ $middleName }}" Фамилия="{{ $lastName }}"/>
                    </СвИП>
                    @else
                    <СвЮЛУч ИННЮЛ="{{ $transferOut->buyer->Inn }}"
                            КПП="{{ $transferOut->buyer->Kpp }}"
                            НаимОрг="{{ $transferOut->buyer->advancedBuyer->consignee ?? $transferOut->buyer->FULLNAME }}"
                    />
                    @endif
                </ИдСв>
                <Адрес>
                    <АдрИнф
                        АдрТекст="{{ $transferOut->buyer->advancedBuyer->consigneeAddress ?? $transferOut->buyer->ADDRESS }}"
                        КодСтр="643"
                        НаимСтран="РОССИЯ"
                    />
                </Адрес>
                @if ($transferOut->buyer->BIK || $transferOut->buyer->RSCHET1)
                <БанкРекв НомерСчета="{{ $transferOut->buyer->RSCHET1 }}">
                    <СвБанк БИК="{{ $transferOut->buyer->BIK }}"
                            КорСчет="{{ $transferOut->buyer->KS }}"
                            НаимБанк="{{ $transferOut->buyer->BANK }}"
                    />
                </БанкРекв>
                @endif
                @if ($transferOut->buyer->PHONES)
                <Контакт>
                    <Тлф>{{ $transferOut->buyer->PHONES }}</Тлф>
                </Контакт>
                @endif
            </ГрузПолуч>
            @foreach($cashFlows as $cf)
                <СвПРД НомерПРД="{{ $cf->NPP }}"
                       ДатаПРД="{{ (new Date($cf->DATA))->format('d.m.Y') }}"
                       СуммаПРД="{{ $cf->MONEYSCHET }}"
                />
            @endforeach
            <СвПокуп>
                <ИдСв>
                    @if (strlen($transferOut->buyer->Inn) == 12)
                    @php
                        $fio2 = preg_replace('/^ИП\s+/ui', '', trim($transferOut->buyer->FULLNAME));
                        $parts2 = preg_split('/\s+/', $fio2);
                        $lastName2 = $parts2[0] ?? '';
                        $firstName2 = $parts2[1] ?? '';
                        $middleName2 = $parts2[2] ?? '';
                    @endphp
                    <СвИП ИННФЛ="{{ $transferOut->buyer->Inn }}">
                        <ФИО Имя="{{ $firstName2 }}" Отчество="{{ $middleName2 }}" Фамилия="{{ $lastName2 }}"/>
                    </СвИП>
                    @else
                    <СвЮЛУч ИННЮЛ="{{ $transferOut->buyer->Inn }}"
                            КПП="{{ $transferOut->buyer->Kpp }}"
                            НаимОрг="{{ $transferOut->buyer->FULLNAME }}"
                    />
                    @endif
                </ИдСв>
                <Адрес>
                    <АдрИнф АдрТекст="{{ $transferOut->buyer->ADDRESS }}" КодСтр="643" НаимСтран="РОССИЯ"/>
                </Адрес>
                @if ($transferOut->buyer->BIK || $transferOut->buyer->RSCHET1)
                <БанкРекв НомерСчета="{{ $transferOut->buyer->RSCHET1 }}">
                    <СвБанк БИК="{{ $transferOut->buyer->BIK }}"
                            КорСчет="{{ $transferOut->buyer->KS }}"
                            НаимБанк="{{ $transferOut->buyer->BANK }}"
                    />
                </БанкРекв>
                @endif
                @if ($transferOut->buyer->PHONES)
                <Контакт>
                    <Тлф>{{ $transferOut->buyer->PHONES }}</Тлф>
                </Контакт>
                @endif
            </СвПокуп>
            @if ($transferOut->invoice->IGK)
                <ДопСвФХЖ1 ИдГосКон="{{ $transferOut->invoice->IGK }}"/>
            @endif
            <ДенИзм КодОКВ="643" НаимОКВ="Российский рубль"/>
        </СвСчФакт>
        <ТаблСчФакт>
            @foreach($transferOutLines as $line)
                <СведТов КолТов="{{ $line->QUAN }}"
                         НаимТов="{{ $line->name->NAME }}"
                         НалСт="{{ VAT::get($transferOut->DATA) }}%"
                         НомСтр="{{ $loop->iteration }}"
                         ОКЕИ_Тов="{{ $line->good->unitCode }}"
                         НаимЕдИзм="{{ $line->good->unitName }}"
                         СтТовБезНДС="{{ $line->amountWithoutVat }}"
                         СтТовУчНал="{{ $line->SUMMAP }}"
                         ЦенаТов="{{ $line->priceWithoutVat }}"
                >
                    @if ($line->countryNumCode)
                        <СвДТ КодПроисх="{{ $line->countryNumCode }}" НомерДТ="{{ $line->GTD }}"/>
                    @endif
                    <ДопСведТов
                        КодТов="{{ $line->GOODSCODE }}"
                        ПрТовРаб="1"
                    >
                        @if ($line->countryNumCode)
                            <КрНаимСтрПр>"{{ $line->STRANA }}"</КрНаимСтрПр>
                        @endif
                    </ДопСведТов>
                    <Акциз>
                        <БезАкциз>без акциза</БезАкциз>
                    </Акциз>
                    <СумНал>
                        <СумНал>{{ str_replace(',', '.', $line->SUMMAP - $line->amountWithoutVat) }}</СумНал>
                    </СумНал>
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
            <СвПер ВидОпер="Продажа" СодОпер="Товары переданы" ДатаПер="{{ \Carbon\Carbon::create($transferOut->DATA)->format('d.m.Y') }}">
                @if (!empty($basis ?? null))
                <ОснПер РеквДатаДок="{{ $basisDate }}"
                        РеквНаимДок="{{ $basis }}"
                        РеквНомерДок="{{ $basisNumber }}"
                />
                @elseif (!empty($transferOut->invoice->basis))
                <ОснПер РеквДатаДок="{{ $transferOut->invoice->basisDate }}"
                        РеквНаимДок="{{ $transferOut->invoice->basis }}"
                        РеквНомерДок="{{ $transferOut->invoice->basisNumber }}"
                />
                @else
                <ОснПер РеквДатаДок="{{ \Carbon\Carbon::create($transferOut->invoice->DATA)->format('d.m.Y') }}"
                        РеквНаимДок="Счет"
                        РеквНомерДок="{{ $transferOut->invoice->NS }}"
                />
                @if ($transferOut->invoice->NZ && $transferOut->buyer->DOGOVOR)
                    <ОснПер РеквДатаДок="{{ \Carbon\Carbon::create($transferOut->invoice->DATA)->format('d.m.Y') }}"
                            РеквНаимДок="Заказ"
                            РеквНомерДок="{{ Str::replace('{NZ}', $transferOut->invoice->NZ, $transferOut->buyer->DOGOVOR) }}"
                    />
                @endif
                @endif
            </СвПер>
        </СвПродПер>
        <Подписант Должн="ДИРЕКТОР" СпосПодтПолном="2">

                <ФИО Имя="Михаил" Отчество="Сергеевич" Фамилия="Верхотуров"/>

        </Подписант>
    </Документ>
</Файл>
