package widget;

import java.awt.Color;
import usu.widget.glass.ButtonImageReflection;

/**
 *
 * @author usu
 */
public class ButtonBig extends ButtonImageReflection {

    /*
     * Serial version UID
     */
    private static final long serialVersionUID = 1L;

    public ButtonBig() {
        super();
        setForeground(new Color(255,255,255));
//        setForeground(new Color(50, 50, 50));
        setFont(new java.awt.Font("Trebuchet MS", 0, 14));
        setBackground(Color.red);

    }
}
