@extends('frontend.layout')
@section('content')
<article class="reading-content article-content">
<h1>Contact us</h1>
<h2>Get in touch</h2>
<p>Readers, visitors, and prospective partners can reach mex.instazoomde.com at <a href="mailto:contact@mex.instazoomde.com">contact@mex.instazoomde.com</a>. Email is our preferred contact channel.</p>
<h2>How we can help</h2>
<ul><li>Feedback about the publication or an article.</li><li>Corrections to inaccurate or outdated information.</li><li>Advertising and partnership inquiries.</li><li>Broken links, pages, or other technical problems.</li><li>Privacy, legal, and content-removal questions.</li></ul>
<h2>Sending a useful message</h2>
<p>Choose a descriptive subject and explain your request clearly. For article issues, include the title and URL. Screenshots can help with technical problems.</p>
<h2>What happens next</h2>
<p>Messages are reviewed and directed to the relevant team. Reply times depend on workload and complexity; some matters need follow-up. General feedback may not receive an individual reply.</p>
<h2>Privacy and removal requests</h2>
<p>Contact information is handled under our <a href="{{ url('/pages/privacy-policy') }}">Privacy Policy</a>. For copyright complaints, follow the <a href="{{ url('/pages/dmca') }}">DMCA process</a> and identify the affected material.</p>
</article>
@endsection
