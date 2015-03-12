function DeCryptString( s )
{
	var n = 0;
	var r = "mailto:";
	var z = 0;
	for( var i = 0; i < s.length/2; i++)
	{
		z = s.substr(i*2, 1);
	    n = s.charCodeAt( i*2+1 );
	    if( n >= 8364 )
	    {
		n = 128;
	    }
	    r += String.fromCharCode( n - z );
	}
	return r;
}

function DeCryptX( s )
{
	location.href=DeCryptString( s );
}