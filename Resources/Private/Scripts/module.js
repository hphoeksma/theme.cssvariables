import React, {Fragment, useState} from 'react';
import ReactDOM from 'react-dom';
import InputColor from "react-input-color";

const ThemeColorPicker = (props) => {
    const [color, setColor] = useState({});
    let target = document.querySelector(props.target);
    target.value = color.hex;
    const updated = () => {
        if (props.initialColor !== color.hex) return <div className="color-picker__new"
                                                          style={{
                                                              backgroundColor: color.hex
                                                          }}
        />
    }
    return (
        <Fragment>
            <div className="color-picker__original"
                style={{
                    backgroundColor: props.initialColor
                }}
            />
            {updated()}
            <InputColor
                initialHexColor={props.initialColor}
                onChange={setColor}
                placement="bottom"
            />
        </Fragment>
    )
};

// Wrap in DOMContentLoaded
window.addEventListener('DOMContentLoaded', () => {
    const reactElements = document.querySelectorAll('.react__placeholder--color-picker');
    reactElements.forEach(el => {
        ReactDOM.render(<ThemeColorPicker initialColor={el.dataset.color} target={el.dataset.target}/>, el);
    });
});
