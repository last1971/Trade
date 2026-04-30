@php
    /** @var \App\Services\Upd\Contracts\UpdSourceInterface $source */
    $firm = $source->getFirm();
    $firmHistory = $source->getFirmHistory();
    $buyer = $source->getBuyer();
    $invoice = $source->getInvoice();
    $lines = $source->getLines();
    $cashFlows = $source->getCashFlows();
    $advanceInvoices = $source->getAdvanceInvoices();
    $documentDate = $source->getDocumentDate();
    $documentNumber = $source->getDocumentNumber();
@endphp
<Файл ИдФайл="{{ $source->getFileId() }}" ВерсФорм="5.03" ВерсПрог="epricing.v1">
    <Документ ВремИнфПр="{{ \Carbon\Carbon::now('+07:00')->format('H.i.s') }}"
              ДатаИнфПр="{{ \Carbon\Carbon::now('+07:00')->format('d.m.Y') }}"
              КНД="1115131"
              Функция="{{ $source->getFunction() }}"
              ПоФактХЖ="{{ $source->getOperationDescription() }}"
              НаимДокОпр="{{ $source->getDocumentName() }}"
              НаимЭконСубСост="{{ $firm->FIRMNAME }}, ИНН/КПП {{ $firm->getAttributes()['INN'] }}"
    >
        <СвСчФакт ДатаДок="{{ $documentDate->format('d.m.Y') }}"
                  НомерДок="{{ $documentNumber }}"
        >
            <СвПрод ОКПО="{{ $firm->OKPO }}">
                <ИдСв>
                    <СвЮЛУч ИННЮЛ="{{ $firm->Inn }}"
                            КПП="{{ $firm->Kpp }}"
                            НаимОрг="{{ $firm->FIRMNAME }}"
                    />
                </ИдСв>
                <Адрес>
                    <АдрИнф АдрТекст="{{ $firm->ADDRESS }}" КодСтр="643" НаимСтран="РОССИЯ" />
                </Адрес>
                <БанкРекв НомерСчета="{{ $firmHistory ? $firmHistory->RS : $firm->RS }}">
                    <СвБанк
                        БИК="{{ $firmHistory ? $firmHistory->BIK : $firm->BIK }}"
                        КорСчет="{{ $firmHistory ? $firmHistory->KS : $firm->KS }}"
                        НаимБанк="{{ $firmHistory ? $firmHistory->BANK : $firm->BANK }}"
                    />
                </БанкРекв>
                <Контакт>
                    <Тлф>{{ $firm->PHONES }}</Тлф>
                    <ЭлПочта>{{ $firm->EMAIL }}</ЭлПочта>
                </Контакт>
            </СвПрод>
            <ГрузОт>
                <ОнЖе>он же</ОнЖе>
            </ГрузОт>
            <ГрузПолуч>
                <ИдСв>
                    @if (strlen($buyer->Inn) == 12)
                        @php
                            $fio = preg_replace('/^ИП\s+/ui', '', trim($buyer->advancedBuyer->consignee ?? $buyer->FULLNAME));
                            $parts = preg_split('/\s+/', $fio);
                            $lastName = $parts[0] ?? '';
                            $firstName = $parts[1] ?? '';
                            $middleName = $parts[2] ?? '';
                        @endphp
                        <СвИП ИННФЛ="{{ $buyer->Inn }}">
                            <ФИО Имя="{{ $firstName }}" Отчество="{{ $middleName }}" Фамилия="{{ $lastName }}"/>
                        </СвИП>
                    @else
                        <СвЮЛУч ИННЮЛ="{{ $buyer->Inn }}"
                                КПП="{{ $buyer->Kpp }}"
                                НаимОрг="{{ $buyer->advancedBuyer->consignee ?? $buyer->FULLNAME }}"
                        />
                    @endif
                </ИдСв>
                <Адрес>
                    <АдрИнф
                        АдрТекст="{{ $buyer->advancedBuyer->consigneeAddress ?? $buyer->ADDRESS }}"
                        КодСтр="643"
                        НаимСтран="РОССИЯ"
                    />
                </Адрес>
                @if ($buyer->BIK || $buyer->RSCHET1)
                    <БанкРекв НомерСчета="{{ $buyer->RSCHET1 }}">
                        <СвБанк БИК="{{ $buyer->BIK }}"
                                КорСчет="{{ $buyer->KS }}"
                                НаимБанк="{{ $buyer->BANK }}"
                        />
                    </БанкРекв>
                @endif
                @if ($buyer->PHONES)
                    <Контакт>
                        <Тлф>{{ $buyer->PHONES }}</Тлф>
                    </Контакт>
                @endif
            </ГрузПолуч>
            @foreach ($cashFlows as $cf)
                <СвПРД НомерПРД="{{ $cf->NPP }}"
                       ДатаПРД="{{ (new Date($cf->DATA))->format('d.m.Y') }}"
                       СуммаПРД="{{ $cf->MONEYSCHET }}"
                />
            @endforeach
            <ДокПодтвОтгрНом РеквНаимДок="{{ $source->getDocumentName() }}" РеквНомерДок="{{ $documentNumber }}" РеквДатаДок="{{ $documentDate->format('d.m.Y') }}" />
            <СвПокуп>
                <ИдСв>
                    @if (strlen($buyer->Inn) == 12)
                        @php
                            $fio2 = preg_replace('/^ИП\s+/ui', '', trim($buyer->FULLNAME));
                            $parts2 = preg_split('/\s+/', $fio2);
                            $lastName2 = $parts2[0] ?? '';
                            $firstName2 = $parts2[1] ?? '';
                            $middleName2 = $parts2[2] ?? '';
                        @endphp
                        <СвИП ИННФЛ="{{ $buyer->Inn }}">
                            <ФИО Имя="{{ $firstName2 }}" Отчество="{{ $middleName2 }}" Фамилия="{{ $lastName2 }}"/>
                        </СвИП>
                    @else
                        <СвЮЛУч ИННЮЛ="{{ $buyer->Inn }}"
                                КПП="{{ $buyer->Kpp }}"
                                НаимОрг="{{ $buyer->FULLNAME }}"
                        />
                    @endif
                </ИдСв>
                <Адрес>
                    <АдрИнф АдрТекст="{{ $buyer->ADDRESS }}" КодСтр="643" НаимСтран="РОССИЯ"/>
                </Адрес>
                @if ($buyer->BIK || $buyer->RSCHET1)
                    <БанкРекв НомерСчета="{{ $buyer->RSCHET1 }}">
                        <СвБанк БИК="{{ $buyer->BIK }}"
                                КорСчет="{{ $buyer->KS }}"
                                НаимБанк="{{ $buyer->BANK }}"
                        />
                    </БанкРекв>
                @endif
                @if ($buyer->PHONES)
                    <Контакт>
                        <Тлф>{{ $buyer->PHONES }}</Тлф>
                    </Контакт>
                @endif
            </СвПокуп>
            <ДенИзм КодОКВ="643" НаимОКВ="Российский рубль"/>
            @php $dopSvNumber = 1; @endphp
            @if ($invoice && $invoice->IGK && $invoice->IGK !== 'NOT1C')
                <ДопСвФХЖ{{ $dopSvNumber++ }} ИдГосКон="{{ $invoice->IGK }}"/>
            @endif
            @if (!empty($advanceInvoices))
                <ДопСвФХЖ{{ $dopSvNumber }}>
                    @foreach ($advanceInvoices as $ai)
                        <СопрДокФХЖ РеквНаимДок="АСЧФ" РеквНомерДок="{{ $ai['number'] }}" РеквДатаДок="{{ $ai['date'] }}"/>
                    @endforeach
                </ДопСвФХЖ{{ $dopSvNumber++ }}>
            @endif
        </СвСчФакт>
        <ТаблСчФакт>
            @foreach ($lines as $line)
                <СведТов КолТов="{{ $line->quantity }}"
                         НаимТов="{{ $line->name }}"
                         НалСт="{{ VAT::get($documentDate, $firm->FIRM_ID ?? null) }}%"
                         НомСтр="{{ $loop->iteration }}"
                         ОКЕИ_Тов="{{ $line->unitCode }}"
                         НаимЕдИзм="{{ $line->unitName }}"
                         СтТовБезНДС="{{ $line->amountWithoutVat }}"
                         СтТовУчНал="{{ $line->amount }}"
                         ЦенаТов="{{ $line->price }}"
                >
                    @if ($line->countryNumCode)
                        <СвДТ КодПроисх="{{ $line->countryNumCode }}" НомерДТ="{{ $line->gtdNumber }}"/>
                    @endif
                    <ДопСведТов
                        КодТов="{{ $line->goodsCode }}"
                        ПрТовРаб="1"
                    >
                        @if ($line->countryNumCode)
                            <КрНаимСтрПр>"{{ $line->strana }}"</КрНаимСтрПр>
                        @endif
                        @if ($line->markCodes && $line->markCodes->isNotEmpty())
                            <НомСредИдентТов>
                                @foreach ($line->markCodes as $mc)
                                    <КИЗ>{{ $mc->KI }}</КИЗ>
                                @endforeach
                            </НомСредИдентТов>
                        @endif
                    </ДопСведТов>
                    <Акциз>
                        <БезАкциз>без акциза</БезАкциз>
                    </Акциз>
                    <СумНал>
                        <СумНал>{{ str_replace(',', '.', $line->amount - $line->amountWithoutVat) }}</СумНал>
                    </СумНал>
                </СведТов>
            @endforeach
            <ВсегоОпл СтТовБезНДСВсего="{{ str_replace(',', '.', $lines->sum('amountWithoutVat')) }}"
                      СтТовУчНалВсего="{{ str_replace(',', '.', $lines->sum('amount')) }}"
            >
                <СумНалВсего>
                    <СумНал>{{ str_replace(',', '.', $lines->sum('amount') - $lines->sum('amountWithoutVat')) }}</СумНал>
                </СумНалВсего>
            </ВсегоОпл>
        </ТаблСчФакт>
        <СвПродПер>
            <СвПер ВидОпер="{{ $source->getOperationType() }}" СодОпер="Товары переданы" ДатаПер="{{ $documentDate->format('d.m.Y') }}">
                @if (!empty($source->getBasis()))
                    <ОснПер РеквДатаДок="{{ $source->getBasisDate() }}"
                            РеквНаимДок="{{ $source->getBasis() }}"
                            РеквНомерДок="{{ $source->getBasisNumber() }}"
                    />
                @elseif ($invoice && !empty($invoice->basis))
                    <ОснПер РеквДатаДок="{{ $invoice->basisDate }}"
                            РеквНаимДок="{{ $invoice->basis }}"
                            РеквНомерДок="{{ $invoice->basisNumber }}"
                    />
                @elseif ($invoice)
                    <ОснПер РеквДатаДок="{{ \Carbon\Carbon::create($invoice->DATA)->format('d.m.Y') }}"
                            РеквНаимДок="Счет"
                            РеквНомерДок="{{ $invoice->NS }}"
                    />
                    @if ($invoice->NZ && $buyer->DOGOVOR)
                        <ОснПер РеквДатаДок="{{ \Carbon\Carbon::create($invoice->DATA)->format('d.m.Y') }}"
                                РеквНаимДок="Заказ"
                                РеквНомерДок="{{ \Illuminate\Support\Str::replace('{NZ}', $invoice->NZ, $buyer->DOGOVOR) }}"
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
