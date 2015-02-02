function DeCryptString( s )
{
	var n = 0;
	var r = "mailto:";
	var h = s.substr( 0, 1 );
	for( var i = 1; i < s.length; i++)
	{
	    n = s.charCodeAt( i );
	    if( n >= 8364 )
	    {
		n = 128;
	    }
	    r += String.fromCharCode( n - h );
	}
	return r;
}

function DeCryptX( s )
{
	location.href=DeCryptString( s );
}