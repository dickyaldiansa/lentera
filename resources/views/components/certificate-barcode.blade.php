@props(['value'])
@php
    $patterns=['0'=>'nnnwwnwnn','1'=>'wnnwnnnnw','2'=>'nnwwnnnnw','3'=>'wnwwnnnnn','4'=>'nnnwwnnnw','5'=>'wnnwwnnnn','6'=>'nnwwwnnnn','7'=>'nnnwnnwnw','8'=>'wnnwnnwnn','9'=>'nnwwnnwnn','A'=>'wnnnnwnnw','B'=>'nnwnnwnnw','C'=>'wnwnnwnnn','D'=>'nnnnwwnnw','E'=>'wnnnwwnnn','F'=>'nnwnwwnnn','G'=>'nnnnnwwnw','H'=>'wnnnnwwnn','I'=>'nnwnnwwnn','J'=>'nnnnwwwnn','K'=>'wnnnnnnww','L'=>'nnwnnnnww','M'=>'wnwnnnnwn','N'=>'nnnnwnnww','O'=>'wnnnwnnwn','P'=>'nnwnwnnwn','Q'=>'nnnnnnwww','R'=>'wnnnnnwwn','S'=>'nnwnnnwwn','T'=>'nnnnwnwwn','U'=>'wwnnnnnnw','V'=>'nwwnnnnnw','W'=>'wwwnnnnnn','X'=>'nwnnwnnnw','Y'=>'wwnnwnnnn','Z'=>'nwwnwnnnn','-'=>'nwnnnnwnw','.'=>'wwnnnnwnn',' '=>'nwwnnnwnn','/'=>'nwnnnwnwn','+'=>'nwnwnnnwn','%'=>'nnnwnwnwn','*'=>'nwnnwnwnn'];
    $chars=str_split('*'.strtoupper($value).'*'); $x=8; $module=1.35; $bars=[];
    foreach($chars as $char){foreach(str_split($patterns[$char]??$patterns['-']) as $index=>$width){$w=($width==='w'?3:1)*$module;if($index%2===0)$bars[]=[$x,$w];$x+=$w;}$x+=$module;}
@endphp
@php
    $token=\App\Models\Certificate::where('number',$value)->value('verification_token');
    $verificationUrl=$token ? route('certificates.verify',$token) : $value;
    $qrCode=new \Endroid\QrCode\QrCode(data:$verificationUrl,size:180,margin:0,errorCorrectionLevel:\Endroid\QrCode\ErrorCorrectionLevel::High);
    $qrDataUri=(new \Endroid\QrCode\Writer\SvgWriter())->write($qrCode)->getDataUri();
@endphp
<img class="certificate-qr" src="{{ $qrDataUri }}" alt="QR code verifikasi sertifikat">
<style>svg.certificate-barcode{display:none!important}.certificate-qr{display:block;width:150px;height:150px;margin:8px 0 6px}</style>
<svg class="certificate-barcode" viewBox="0 0 {{ $x+8 }} 52" role="img" aria-label="Barcode {{ $value }}" preserveAspectRatio="none">@foreach($bars as [$left,$width])<rect x="{{ $left }}" y="1" width="{{ $width }}" height="39" fill="#102d61"/>@endforeach<text x="{{ ($x+8)/2 }}" y="50" text-anchor="middle" font-size="7" fill="#102d61" letter-spacing="1">{{ $value }}</text></svg>
